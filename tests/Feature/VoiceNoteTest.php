<?php

namespace Tests\Feature;

use App\Application\Voice\SendVoiceNote;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Setting;
use App\Domain\Models\Transaction;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Nota de voz del widget: el texto transcrito viaja al servidor, el reply
 * vuelve en el JSON y, si se encoló un movimiento, se aplica al instante
 * con el pipeline de WhatsApp.
 */
class VoiceNoteTest extends TestCase
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
     * @param  list<array<string, mixed>>  $pendingMessages
     */
    private function fakeServer(array $voiceResponse, array $pendingMessages = []): void
    {
        Http::fake([
            'https://sync.test/api/devices' => Http::response(['device_id' => (string) Str::uuid(), 'api_token' => 'fresh-token']),
            'https://sync.test/api/voice-notes' => Http::response($voiceResponse),
            'https://sync.test/api/messages/pending' => Http::response(['data' => $pendingMessages]),
            'https://sync.test/api/messages/ack' => Http::response(['acked' => count($pendingMessages)]),
            'https://sync.test/api/devices/me/accounts' => Http::response(null, 204),
        ]);
    }

    public function test_registers_the_device_on_first_voice_note_without_linking_whatsapp(): void
    {
        $this->fakeServer(['reply' => 'Listo ✅', 'message_created' => false]);

        $result = app(SendVoiceNote::class)->handle('cuánto tengo');

        $this->assertTrue($result['ok']);
        $this->assertSame('fresh-token', app(WhatsAppLink::class)->apiToken());

        // Registrarse no vincula ningún teléfono: eso sigue siendo del
        // flujo de WhatsApp.
        $this->assertFalse(app(WhatsAppLink::class)->isLinked());

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/devices'));
    }

    public function test_reports_an_error_when_registration_fails(): void
    {
        Http::fake(['https://sync.test/api/devices' => Http::response(null, 500)]);

        $result = app(SendVoiceNote::class)->handle('gasté 20 en comida');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('conexión', $result['reply']);
    }

    public function test_uploads_the_accounts_snapshot_only_when_it_changed(): void
    {
        $this->linkDevice();
        Account::factory()->currency('PEN')->withInitialBalance(200)->create();
        $this->fakeServer(['reply' => 'Listo ✅', 'message_created' => false]);

        app(SendVoiceNote::class)->handle('cuánto tengo');
        app(SendVoiceNote::class)->handle('cuánto tengo');

        $snapshotCalls = 0;
        Http::assertSent(function (Request $request) use (&$snapshotCalls): bool {
            if (str_ends_with($request->url(), '/api/devices/me/accounts')) {
                $snapshotCalls++;
            }

            return true;
        });

        $this->assertSame(1, $snapshotCalls);
    }

    public function test_rejects_empty_text(): void
    {
        $this->linkDevice();
        Http::fake();

        $result = app(SendVoiceNote::class)->handle('   ');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_sends_voice_note_and_applies_created_movement(): void
    {
        $account = Account::factory()->currency('PEN')->withInitialBalance(200)->create();
        Category::factory()->expense()->create(['name' => 'Comida']);
        $this->linkDevice();

        $this->fakeServer(
            ['reply' => '📝 Gasto de 20.00 · comida · hoy.', 'message_created' => true],
            [[
                'id' => (string) Str::uuid(),
                'type' => 'expense',
                'amount' => '20.00',
                'category_text' => 'comida',
                'account_text' => null,
                'account_id' => $account->id,
                'description' => null,
                'meta' => null,
                'occurred_on' => today()->toDateString(),
                'raw_text' => 'gasté 20 en comida',
                'received_at' => now()->toIso8601String(),
            ]],
        );

        $result = app(SendVoiceNote::class)->handle('gasté 20 en comida');

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['applied']);
        $this->assertSame('📝 Gasto de 20.00 · comida · hoy.', $result['reply']);
        $this->assertSame(1, Transaction::count());

        Http::assertSent(function (Request $request): bool {
            return str_ends_with($request->url(), '/api/voice-notes')
                && $request['text'] === 'gasté 20 en comida'
                && Str::isUuid($request['client_message_id']);
        });
    }

    public function test_query_reply_does_not_pull_pending_messages(): void
    {
        $this->linkDevice();
        $this->fakeServer(['reply' => '💳 BCP: 250.00 PEN', 'message_created' => false]);

        $result = app(SendVoiceNote::class)->handle('cuánto tengo en bcp');

        $this->assertTrue($result['ok']);
        $this->assertSame(0, $result['applied']);
        $this->assertSame('💳 BCP: 250.00 PEN', $result['reply']);

        Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/messages/pending'));
    }

    public function test_reports_a_readable_error_when_server_is_unreachable(): void
    {
        $this->linkDevice();
        Http::fake(['https://sync.test/api/voice-notes' => Http::response(null, 500)]);

        $result = app(SendVoiceNote::class)->handle('gasté 20 en comida');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('conexión', $result['reply']);
    }

    public function test_headless_command_prints_json_result(): void
    {
        $this->linkDevice();
        $this->fakeServer(['reply' => 'Listo ✅', 'message_created' => false]);

        $this->artisan('voice:send-headless', ['--text-base64' => base64_encode('cuánto tengo')])
            ->expectsOutput((string) json_encode(['reply' => 'Listo ✅', 'applied' => 0, 'ok' => true], JSON_UNESCAPED_UNICODE))
            ->assertSuccessful();
    }

    public function test_web_route_flashes_the_reply(): void
    {
        $this->linkDevice();
        $this->fakeServer(['reply' => '💳 BCP: 250.00 PEN', 'message_created' => false]);

        $this->post('/voice-notes', ['text' => 'cuánto tengo en bcp'])
            ->assertRedirect()
            ->assertSessionHas('success', '💳 BCP: 250.00 PEN');
    }
}
