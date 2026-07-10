<?php

namespace Tests\Feature;

use App\Application\WhatsApp\ApplyPendingMessages;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Setting;
use App\Domain\Models\Transaction;
use App\Domain\Models\WhatsAppInboxEntry;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplyPendingMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp_sync.url' => 'https://sync.test']);
    }

    private function linkDevice(?Account $defaultAccount = null): void
    {
        Setting::put(WhatsAppLink::DEVICE_ID_KEY, (string) Str::uuid());
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'test-token');
        Setting::put(WhatsAppLink::LINKED_KEY, '1');

        if ($defaultAccount !== null) {
            Setting::put(WhatsAppLink::DEFAULT_ACCOUNT_KEY, $defaultAccount->id);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function fakeServer(array $messages): void
    {
        Http::fake([
            'https://sync.test/api/messages/pending' => Http::response(['data' => $messages]),
            'https://sync.test/api/messages/ack' => Http::response(['acked' => count($messages)]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function message(array $overrides = []): array
    {
        return array_merge([
            'id' => (string) Str::uuid(),
            'type' => 'expense',
            'amount' => '100.00',
            'category_text' => 'comida',
            'description' => null,
            'occurred_on' => today()->toDateString(),
            'raw_text' => 'comida 100 hoy',
            'received_at' => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_applies_an_expense_with_matched_category(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        $this->linkDevice($account);
        $this->fakeServer([$this->message()]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(0, $result->failed);

        $transaction = Transaction::sole();
        $this->assertSame('expense', $transaction->type->value);
        $this->assertSame(10000, $transaction->amount->minorUnits);
        $this->assertSame($category->id, $transaction->category_id);
        $this->assertSame(90000, $account->fresh()->current_balance->minorUnits);

        $this->assertSame(WhatsAppInboxEntry::STATUS_APPLIED, WhatsAppInboxEntry::sole()->status);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/ack')
            && $request['results'][0]['status'] === 'applied');
    }

    public function test_applies_an_income_in_the_account_currency(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(0)->create();
        Category::factory()->income()->create(['name' => 'Sueldo']);
        $this->linkDevice($account);
        $this->fakeServer([$this->message([
            'type' => 'income',
            'amount' => '2500.00',
            'category_text' => 'sueldo',
            'raw_text' => 'ingreso sueldo 2500',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(250000, $account->fresh()->current_balance->minorUnits);
        $this->assertSame('ARS', Transaction::sole()->currency);
    }

    public function test_unmatched_category_lands_as_description(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice($account);
        $this->fakeServer([$this->message(['category_text' => 'tragamonedas', 'raw_text' => '100 tragamonedas'])]);

        app(ApplyPendingMessages::class)->handle();

        $transaction = Transaction::sole();
        $this->assertNull($transaction->category_id);
        $this->assertSame('tragamonedas', $transaction->description);
    }

    public function test_insufficient_balance_rejects_and_acks_failed(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(50)->create();
        $this->linkDevice($account);
        $this->fakeServer([$this->message(['amount' => '100.00'])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(0, $result->applied);
        $this->assertSame(1, $result->failed);
        $this->assertDatabaseCount('transactions', 0);

        $entry = WhatsAppInboxEntry::sole();
        $this->assertSame(WhatsAppInboxEntry::STATUS_FAILED, $entry->status);
        $this->assertStringContainsString('Saldo insuficiente', $entry->reason);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/ack')
            && $request['results'][0]['status'] === 'failed');
    }

    public function test_without_default_account_messages_stay_pending_on_server(): void
    {
        $this->linkDevice();
        $this->fakeServer([$this->message()]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertTrue($result->needsAccountSetup);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('whatsapp_inbox', 0);

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/ack'));
    }

    public function test_already_seen_messages_are_reacked_without_reapplying(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice($account);

        $message = $this->message();
        WhatsAppInboxEntry::create([
            'id' => $message['id'],
            'status' => WhatsAppInboxEntry::STATUS_APPLIED,
            'raw_text' => $message['raw_text'],
        ]);
        $this->fakeServer([$message]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(0, $result->applied);
        $this->assertDatabaseCount('transactions', 0);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/ack')
            && $request['results'][0]['status'] === 'applied');
    }

    public function test_consecutive_expenses_validate_against_updated_balance(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(150)->create();
        $this->linkDevice($account);
        $this->fakeServer([
            $this->message(['amount' => '100.00']),
            $this->message(['amount' => '100.00', 'raw_text' => 'taxi 100']),
        ]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(1, $result->failed);
        $this->assertSame(5000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_server_down_returns_empty_result(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice($account);
        Http::fake(fn () => throw new ConnectionException('offline'));

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertFalse($result->hasChanges());
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_unlinked_app_never_calls_the_server(): void
    {
        Http::fake();

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertFalse($result->hasChanges());
        Http::assertNothingSent();
    }
}
