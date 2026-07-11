<?php

namespace App\Application\WhatsApp;

use App\Domain\Models\Setting;
use App\Infrastructure\Http\WebhookServerClient;
use App\Support\WhatsAppLink;
use App\Support\WhatsAppSyncNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Punto único de sincronización de WhatsApp: aplica los pendientes, arma el
 * flash y la notificación local, y asegura el enroll de push. Con throttle
 * para poder dispararlo desde cualquier pantalla (apertura, volver del
 * background, navegación) sin martillar el servidor. Nunca lanza: un sync
 * fallido no debe romper ninguna página.
 */
class SyncWhatsApp
{
    public const THROTTLE_SECONDS = 60;

    private const THROTTLE_KEY = 'whatsapp_last_sync';

    private const FCM_TOKEN_KEY = 'whatsapp_fcm_token';

    public function __construct(
        private readonly ApplyPendingMessages $applyPendingMessages,
        private readonly WhatsAppSyncNotifier $notifier,
        private readonly WhatsAppLink $link,
        private readonly WebhookServerClient $client,
    ) {}

    /**
     * @param  bool  $force  Salta el throttle: el push FCM garantiza que hay
     *                       un mensaje nuevo esperando en el servidor.
     */
    public function handle(bool $force = false): void
    {
        if (! $this->link->isConfigured() || ! $this->link->isLinked()) {
            return;
        }

        if (! $force && Cache::has(self::THROTTLE_KEY)) {
            return;
        }

        Cache::put(self::THROTTLE_KEY, now()->timestamp, self::THROTTLE_SECONDS);

        try {
            $result = $this->applyPendingMessages->handle();

            if ($result->hasChanges()) {
                $summary = $this->summary($result->applied, $result->failed);
                session()->flash('success', $summary);
                $this->notifier->notify($summary);
            }
        } catch (Throwable $e) {
            Log::warning('WhatsApp sync failed: '.$e->getMessage());
        }

        $this->syncFcmToken();
    }

    private function summary(int $applied, int $failed): string
    {
        $parts = [];

        if ($applied > 0) {
            $parts[] = $applied === 1
                ? 'Se registró 1 movimiento de WhatsApp'
                : "Se registraron {$applied} movimientos de WhatsApp";
        }

        if ($failed > 0) {
            $parts[] = $failed === 1 ? '1 falló (revisa Ajustes)' : "{$failed} fallaron (revisa Ajustes)";
        }

        return implode('; ', $parts).'.';
    }

    /**
     * Obtiene el token FCM vía el bridge del plugin propio (el core de
     * NativePHP expone PushNotifications::enroll() pero el scaffold no
     * implementa ningún bridge PushNotification.*, así que ese camino
     * falla en silencio) y lo sube al servidor. Se cachea el último token
     * subido para no repetir el PUT en cada sync; si rota, se re-sube solo.
     */
    private function syncFcmToken(): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        try {
            $result = json_decode((string) nativephp_call('WhatsAppSync.GetFcmToken', '{}'), true);
            $token = is_array($result) ? ($result['token'] ?? null) : null;

            if (! is_string($token) || $token === '' || Setting::get(self::FCM_TOKEN_KEY) === $token) {
                return;
            }

            $this->client->updateFcmToken($token);
            Setting::put(self::FCM_TOKEN_KEY, $token);
            Log::info('WhatsApp FCM token uploaded.');
        } catch (Throwable $e) {
            Log::info('FCM token sync skipped: '.$e->getMessage());
        }
    }
}
