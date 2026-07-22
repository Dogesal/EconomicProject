<?php

namespace App\Application\Voice;

use App\Application\WhatsApp\AccountsSnapshot;
use App\Application\WhatsApp\ApplyPendingMessages;
use App\Domain\Models\Setting;
use App\Infrastructure\Http\WebhookServerClient;
use App\Support\WhatsAppLink;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envía el texto transcrito por el widget de voz al servidor (que lo
 * interpreta con el LLM) y, si se encoló un movimiento, lo aplica de
 * inmediato en la BD local con el pipeline de WhatsApp. Nunca lanza:
 * el resultado siempre es un array con un mensaje legible para mostrar.
 *
 * No exige vincular WhatsApp: el servidor solo pide credenciales de
 * dispositivo, así que la primera nota de voz registra el dispositivo sola.
 */
class SendVoiceNote
{
    /**
     * Hash del último snapshot de cuentas subido, para no repetir el PUT
     * cuando nada cambió (el LLM necesita saldos frescos para validar y
     * para responder consultas).
     */
    private const ACCOUNTS_HASH_KEY = 'voice_accounts_hash';

    public function __construct(
        private readonly WebhookServerClient $client,
        private readonly ApplyPendingMessages $applyPendingMessages,
        private readonly WhatsAppLink $link,
    ) {}

    /**
     * @return array{reply: string, applied: int, ok: bool}
     */
    public function handle(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return ['reply' => 'No escuché nada 🎙️ Intenta de nuevo.', 'applied' => 0, 'ok' => false];
        }

        if (! $this->link->isConfigured()) {
            return [
                'reply' => 'El registro por voz no está disponible en esta versión de la app.',
                'applied' => 0,
                'ok' => false,
            ];
        }

        if (! $this->link->isRegistered() && ! $this->registerDevice()) {
            return [
                'reply' => 'No pude conectar con el servidor 📡 Revisa tu conexión e intenta de nuevo.',
                'applied' => 0,
                'ok' => false,
            ];
        }

        $this->pushAccountsSnapshotIfChanged();

        try {
            $result = $this->client->sendVoiceNote($text, (string) Str::uuid());
        } catch (Throwable $e) {
            Log::warning('Voice note failed: '.$e->getMessage());

            return ['reply' => 'No pude enviar tu nota de voz 📡 Revisa tu conexión e intenta de nuevo.', 'applied' => 0, 'ok' => false];
        }

        $applied = 0;

        if ($result['message_created'] ?? false) {
            try {
                $applied = $this->applyPendingMessages->handle()->applied;
            } catch (Throwable $e) {
                Log::warning('Voice note apply failed: '.$e->getMessage());
            }
        }

        return [
            'reply' => (string) ($result['reply'] ?? 'Listo ✅'),
            'applied' => $applied,
            'ok' => true,
        ];
    }

    /**
     * Registra el dispositivo contra el servidor la primera vez que se usa
     * la voz. No vincula ningún teléfono: eso sigue siendo exclusivo del
     * flujo de WhatsApp (Ajustes → WhatsApp).
     */
    private function registerDevice(): bool
    {
        try {
            $registration = $this->client->register('Mi Economía Android');
            $this->link->storeDevice($registration['device_id'], $registration['api_token']);

            return true;
        } catch (Throwable $e) {
            Log::warning('Voice device registration failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sube cuentas, categorías, deudas y resumen cuando cambiaron desde el
     * último envío. Best-effort: sin red la nota de voz igual se intenta.
     */
    private function pushAccountsSnapshotIfChanged(): void
    {
        try {
            $snapshot = AccountsSnapshot::build();
            $hash = md5((string) json_encode($snapshot));

            if (Setting::get(self::ACCOUNTS_HASH_KEY) === $hash) {
                return;
            }

            $this->client->syncAccounts($snapshot);
            Setting::put(self::ACCOUNTS_HASH_KEY, $hash);
        } catch (Throwable $e) {
            Log::info('Voice accounts snapshot skipped: '.$e->getMessage());
        }
    }
}
