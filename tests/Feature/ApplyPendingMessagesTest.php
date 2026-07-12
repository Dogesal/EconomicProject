<?php

namespace Tests\Feature;

use App\Application\WhatsApp\ApplyPendingMessages;
use App\Domain\Enums\DebtDirection;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Debt;
use App\Domain\Models\Setting;
use App\Domain\Models\Transaction;
use App\Domain\Models\WhatsAppInboxEntry;
use App\Domain\ValueObjects\Money;
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

    private function linkDevice(): void
    {
        Setting::put(WhatsAppLink::DEVICE_ID_KEY, (string) Str::uuid());
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'test-token');
        Setting::put(WhatsAppLink::LINKED_KEY, '1');
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function fakeServer(array $messages): void
    {
        Http::fake([
            'https://sync.test/api/messages/pending' => Http::response(['data' => $messages]),
            'https://sync.test/api/messages/ack' => Http::response(['acked' => count($messages)]),
            'https://sync.test/api/devices/me/accounts' => Http::response(null, 204),
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
            'account_text' => null,
            'account_id' => null,
            'description' => null,
            'meta' => null,
            'occurred_on' => today()->toDateString(),
            'raw_text' => 'comida 100 hoy',
            'received_at' => now()->toIso8601String(),
        ], $overrides);
    }

    public function test_applies_an_expense_with_matched_category(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        $this->linkDevice();
        $this->fakeServer([$this->message(['account_id' => $account->id])]);

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
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'income',
            'amount' => '2500.00',
            'category_text' => 'sueldo',
            'raw_text' => 'ingreso sueldo 2500',
            'account_id' => $account->id,
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(250000, $account->fresh()->current_balance->minorUnits);
        $this->assertSame('ARS', Transaction::sole()->currency);
    }

    public function test_unmatched_category_lands_as_description(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'category_text' => 'tragamonedas',
            'raw_text' => '100 tragamonedas',
            'account_id' => $account->id,
        ])]);

        app(ApplyPendingMessages::class)->handle();

        $transaction = Transaction::sole();
        $this->assertNull($transaction->category_id);
        $this->assertSame('tragamonedas', $transaction->description);
    }

    public function test_insufficient_balance_rejects_and_acks_failed(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(50)->create();
        $this->linkDevice();
        $this->fakeServer([$this->message(['amount' => '100.00', 'account_id' => $account->id])]);

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

    public function test_account_id_routes_transaction_to_that_account(): void
    {
        $other = Account::factory()->currency('ARS')->withInitialBalance(1000)->create(['name' => 'Efectivo']);
        $bcp = Account::factory()->currency('ARS')->withInitialBalance(500)->create(['name' => 'BCP Soles']);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'account_id' => $bcp->id,
            'account_text' => 'BCP Soles',
            'raw_text' => 'comida 100 cuenta bcp',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame($bcp->id, Transaction::sole()->account_id);
        $this->assertSame(40000, $bcp->fresh()->current_balance->minorUnits);
        $this->assertSame(100000, $other->fresh()->current_balance->minorUnits);
    }

    public function test_unknown_account_id_falls_back_to_account_text(): void
    {
        $bcp = Account::factory()->currency('ARS')->withInitialBalance(500)->create(['name' => 'BCP Soles']);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            // Un id que ya no existe en la app (p.ej. cuenta recreada).
            'account_id' => (string) Str::uuid(),
            'account_text' => 'bcp',
            'raw_text' => 'comida 100 cuenta bcp',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame($bcp->id, Transaction::sole()->account_id);
    }

    public function test_unknown_account_text_fails_with_reason(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create(['name' => 'Efectivo']);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'account_text' => 'interbank',
            'raw_text' => 'comida 100 cuenta interbank',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->failed);
        $this->assertDatabaseCount('transactions', 0);

        $entry = WhatsAppInboxEntry::sole();
        $this->assertSame(WhatsAppInboxEntry::STATUS_FAILED, $entry->status);
        $this->assertStringContainsString('interbank', $entry->reason);
    }

    public function test_message_without_account_fails_with_reason(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
        $this->fakeServer([$this->message()]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->failed);
        $this->assertDatabaseCount('transactions', 0);

        $entry = WhatsAppInboxEntry::sole();
        $this->assertSame(WhatsAppInboxEntry::STATUS_FAILED, $entry->status);
        $this->assertStringContainsString('Elige la cuenta', $entry->reason);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/ack')
            && $request['results'][0]['status'] === 'failed');
    }

    public function test_debt_message_creates_a_debt_without_touching_balances(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'debt',
            'amount' => '50.00',
            'category_text' => 'juan',
            'raw_text' => 'deuda 50 juan',
            'account_id' => $account->id,
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertSame(100000, $account->fresh()->current_balance->minorUnits);

        $debt = Debt::sole();
        $this->assertSame('Juan', $debt->name);
        $this->assertSame(DebtDirection::IOwe, $debt->direction);
        $this->assertSame(5000, $debt->original_amount->minorUnits);
        $this->assertSame('ARS', $debt->currency);

        $this->assertSame(WhatsAppInboxEntry::STATUS_APPLIED, WhatsAppInboxEntry::sole()->status);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/ack')
            && $request['results'][0]['status'] === 'applied');
    }

    public function test_already_seen_messages_are_reacked_without_reapplying(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();

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
        $this->linkDevice();
        $this->fakeServer([
            $this->message(['amount' => '100.00', 'account_id' => $account->id]),
            $this->message(['amount' => '100.00', 'raw_text' => 'taxi 100', 'account_id' => $account->id]),
        ]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(1, $result->failed);
        $this->assertSame(5000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_accounts_snapshot_is_uploaded_after_sync(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create(['name' => 'BCP Soles']);
        Account::factory()->currency('ARS')->archived()->create(['name' => 'Archivada']);
        $this->linkDevice();
        $this->fakeServer([]);

        app(ApplyPendingMessages::class)->handle();

        // Solo las cuentas activas viajan, con su saldo fresco.
        Http::assertSent(function (Request $request) use ($account): bool {
            if (! str_ends_with($request->url(), '/api/devices/me/accounts')) {
                return false;
            }

            $accounts = $request['accounts'];

            return count($accounts) === 1
                && $accounts[0]['id'] === $account->id
                && $accounts[0]['name'] === 'BCP Soles'
                && $accounts[0]['currency'] === 'ARS';
        });
    }

    public function test_transfer_moves_money_between_accounts(): void
    {
        $from = Account::factory()->currency('ARS')->withInitialBalance(500)->create(['name' => 'BCP Soles']);
        $to = Account::factory()->currency('ARS')->withInitialBalance(100)->create(['name' => 'Efectivo']);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'transfer',
            'amount' => '100.00',
            'category_text' => null,
            'account_id' => $from->id,
            'meta' => ['to_account_id' => $to->id, 'to_account_name' => 'Efectivo'],
            'raw_text' => 'pasa 100 de bcp a efectivo',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(40000, $from->fresh()->current_balance->minorUnits);
        $this->assertSame(20000, $to->fresh()->current_balance->minorUnits);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(WhatsAppInboxEntry::STATUS_APPLIED, WhatsAppInboxEntry::sole()->status);
    }

    public function test_transfer_to_unknown_account_fails_with_reason(): void
    {
        $from = Account::factory()->currency('ARS')->withInitialBalance(500)->create(['name' => 'BCP Soles']);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'transfer',
            'amount' => '100.00',
            'account_id' => $from->id,
            'meta' => ['to_account_id' => (string) Str::uuid(), 'to_account_name' => 'Efectivo'],
            'raw_text' => 'pasa 100 de bcp a efectivo',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->failed);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertStringContainsString('Efectivo', WhatsAppInboxEntry::sole()->reason);
    }

    public function test_debt_payment_registers_expense_and_updates_debt(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(500)->create();
        $debt = Debt::factory()->create([
            'name' => 'Juan',
            'direction' => DebtDirection::IOwe,
            'original_amount' => 10000,
            'paid_amount' => 0,
            'currency' => 'ARS',
        ]);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'debt_payment',
            'amount' => '50.00',
            'account_id' => $account->id,
            'meta' => ['debt_name' => 'juan'],
            'raw_text' => 'pagué 50 de la deuda de juan',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertSame(45000, $account->fresh()->current_balance->minorUnits);
        $this->assertSame(5000, $debt->fresh()->paid_amount->minorUnits);

        $transaction = Transaction::sole();
        $this->assertSame($debt->id, $transaction->debt_id);
    }

    public function test_debt_payment_for_unknown_debt_fails_with_reason(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(500)->create();
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'debt_payment',
            'amount' => '50.00',
            'account_id' => $account->id,
            'meta' => ['debt_name' => 'pedro'],
            'raw_text' => 'pagué 50 de la deuda de pedro',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->failed);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertStringContainsString('pedro', WhatsAppInboxEntry::sole()->reason);
    }

    public function test_create_category_message_creates_it(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(100)->create();
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'create_category',
            'amount' => '0.00',
            'category_text' => 'mascotas',
            'meta' => ['category_type' => 'expense'],
            'raw_text' => 'crea la categoría mascotas',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);

        $category = Category::sole();
        $this->assertSame('Mascotas', $category->name);
        $this->assertSame('expense', $category->type->value);
    }

    public function test_create_existing_category_is_idempotent(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(100)->create();
        Category::factory()->expense()->create(['name' => 'Mascotas']);
        $this->linkDevice();
        $this->fakeServer([$this->message([
            'type' => 'create_category',
            'amount' => '0.00',
            'category_text' => 'mascotas',
            'meta' => ['category_type' => 'expense'],
            'raw_text' => 'crea la categoría mascotas',
        ])]);

        $result = app(ApplyPendingMessages::class)->handle();

        $this->assertSame(1, $result->applied);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_snapshot_includes_categories_debts_and_summary(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create(['name' => 'BCP Soles']);
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        Debt::factory()->create([
            'name' => 'Juan',
            'direction' => DebtDirection::IOwe,
            'original_amount' => 10000,
            'paid_amount' => 0,
            'currency' => 'ARS',
        ]);
        $account->transactions()->create([
            'type' => 'expense',
            'amount' => Money::fromDecimal('200', 'ARS'),
            'currency' => 'ARS',
            'is_inflow' => false,
            'category_id' => $category->id,
            'occurred_on' => today(),
        ]);
        $this->linkDevice();
        $this->fakeServer([]);

        app(ApplyPendingMessages::class)->handle();

        Http::assertSent(function (Request $request): bool {
            if (! str_ends_with($request->url(), '/api/devices/me/accounts')) {
                return false;
            }

            return $request['categories'][0]['name'] === 'Comida'
                && $request['debts'][0]['name'] === 'Juan'
                && $request['debts'][0]['direction'] === 'i_owe'
                && $request['summary']['month'] === now()->format('Y-m')
                && (float) $request['summary']['by_currency'][0]['expense'] === 200.0
                && $request['summary']['top_categories'][0]['name'] === 'Comida';
        });
    }

    public function test_server_down_returns_empty_result(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
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
