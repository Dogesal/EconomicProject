<?php

namespace Tests\Feature;

use App\Domain\Models\Account;
use App\Domain\Models\Setting;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp_sync.url' => 'https://sync.test']);
    }

    private function linkDevice(): void
    {
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

    public function test_sync_endpoint_applies_pending_messages_and_flashes(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
        $this->fakeServer([[
            'id' => (string) Str::uuid(),
            'type' => 'expense',
            'amount' => '100.00',
            'category_text' => 'comida',
            'account_text' => null,
            'account_id' => $account->id,
            'description' => null,
            'occurred_on' => today()->toDateString(),
            'raw_text' => 'comida 100 hoy',
            'received_at' => now()->toIso8601String(),
        ]]);

        $this->from('/settings')->post(route('whatsapp.sync'))
            ->assertRedirect('/settings')
            ->assertSessionHas('success');

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(90000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_sync_is_throttled(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
        $this->fakeServer([]);

        $this->post(route('whatsapp.sync'))->assertRedirect();
        $this->post(route('whatsapp.sync'))->assertRedirect();

        // Solo el primer POST llega al servidor (pull + snapshot); el
        // segundo cae en el throttle de 60s.
        Http::assertSentCount(2);
    }

    public function test_force_bypasses_the_throttle(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        $this->linkDevice();
        $this->fakeServer([]);

        $this->post(route('whatsapp.sync'))->assertRedirect();
        // El push FCM garantiza mensaje nuevo: fuerza el pull aunque el
        // throttle siga activo.
        $this->post(route('whatsapp.sync'), ['force' => 1])->assertRedirect();

        // 2 requests por sync (pull + snapshot): ambos POST llegaron.
        Http::assertSentCount(4);
    }

    public function test_sync_without_link_makes_no_network_calls(): void
    {
        Http::fake();

        $this->post(route('whatsapp.sync'))->assertRedirect();

        Http::assertNothingSent();
    }
}
