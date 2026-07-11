<?php

namespace Tests\Feature;

use App\Domain\Models\Account;
use App\Domain\Models\Setting;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp_sync.url' => 'https://sync.test']);
    }

    public function test_link_registers_device_and_flashes_the_code(): void
    {
        Http::fake([
            'https://sync.test/api/devices' => Http::response([
                'device_id' => (string) Str::uuid(),
                'api_token' => 'fresh-token',
            ], 201),
            'https://sync.test/api/link-codes' => Http::response([
                'code' => '483920',
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
                'bot_phone' => '+51900000000',
            ], 201),
        ]);

        $response = $this->post(route('settings.whatsapp.link'));

        $response->assertSessionHas('whatsappLinkCode', fn (array $flash): bool => $flash['code'] === '483920');
        $this->assertSame('fresh-token', Setting::get(WhatsAppLink::API_TOKEN_KEY));
        $this->assertSame('+51900000000', Setting::get(WhatsAppLink::BOT_PHONE_KEY));
    }

    public function test_link_reuses_existing_device_registration(): void
    {
        Setting::put(WhatsAppLink::DEVICE_ID_KEY, (string) Str::uuid());
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'existing-token');

        Http::fake([
            'https://sync.test/api/link-codes' => Http::response([
                'code' => '111222',
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
                'bot_phone' => '+51900000000',
            ], 201),
        ]);

        $this->post(route('settings.whatsapp.link'))->assertSessionHas('whatsappLinkCode');

        Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/api/devices'));
        $this->assertSame('existing-token', Setting::get(WhatsAppLink::API_TOKEN_KEY));
    }

    public function test_link_fails_gracefully_when_server_is_down(): void
    {
        Http::fake(['https://sync.test/*' => Http::response(null, 500)]);

        $this->post(route('settings.whatsapp.link'))->assertSessionHas('error');

        $this->assertNull(Setting::get(WhatsAppLink::LINKED_KEY));
    }

    public function test_refresh_marks_linked_and_uploads_accounts_snapshot(): void
    {
        $account = Account::factory()->create(['name' => 'BCP Soles']);
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'token');
        Http::fake([
            'https://sync.test/api/devices/me' => Http::response([
                'linked' => true,
                'phone_masked' => '+•••••4321',
                'linked_at' => now()->toIso8601String(),
            ]),
            'https://sync.test/api/devices/me/accounts' => Http::response(null, 204),
        ]);

        $this->post(route('settings.whatsapp.refresh'))->assertSessionHas('success');

        $this->assertSame('1', Setting::get(WhatsAppLink::LINKED_KEY));

        // El bot recibe las cuentas de inmediato para poder preguntar.
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/api/devices/me/accounts')
            && $request['accounts'][0]['id'] === $account->id);
    }

    public function test_refresh_keeps_unlinked_when_code_not_sent_yet(): void
    {
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'token');
        Http::fake([
            'https://sync.test/api/devices/me' => Http::response(['linked' => false, 'phone_masked' => null, 'linked_at' => null]),
        ]);

        $this->post(route('settings.whatsapp.refresh'))->assertSessionHas('error');

        $this->assertNull(Setting::get(WhatsAppLink::LINKED_KEY));
    }

    public function test_unlink_clears_local_state_even_if_server_fails(): void
    {
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'token');
        Setting::put(WhatsAppLink::LINKED_KEY, '1');
        Setting::put('whatsapp_default_account_id', (string) Str::uuid());
        Http::fake(['https://sync.test/*' => Http::response(null, 500)]);

        $this->delete(route('settings.whatsapp.unlink'))->assertSessionHas('success');

        $this->assertNull(Setting::get(WhatsAppLink::LINKED_KEY));
        // La clave heredada de la cuenta destino también se limpia.
        $this->assertNull(Setting::get('whatsapp_default_account_id'));
    }
}
