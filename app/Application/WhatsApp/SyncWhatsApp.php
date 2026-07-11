<?php

namespace App\Application\WhatsApp;

use App\Domain\Models\Setting;
use App\Support\WhatsAppLink;
use App\Support\WhatsAppSyncNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Native\Mobile\Facades\PushNotifications;
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

    private const PUSH_ENROLLED_KEY = 'whatsapp_push_enrolled';

    public function __construct(
        private readonly ApplyPendingMessages $applyPendingMessages,
        private readonly WhatsAppSyncNotifier $notifier,
        private readonly WhatsAppLink $link,
    ) {}

    public function handle(): void
    {
        if (! $this->link->isConfigured() || ! $this->link->isLinked()) {
            return;
        }

        if (Cache::has(self::THROTTLE_KEY)) {
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

        $this->enrollPushOnce();
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
     * Genera el token FCM una sola vez para dispositivos que se vincularon
     * antes de que existiera el push. StorePushToken lo sube al servidor
     * cuando llega TokenGenerated.
     */
    private function enrollPushOnce(): void
    {
        if (! function_exists('nativephp_call') || Setting::get(self::PUSH_ENROLLED_KEY) === '1') {
            return;
        }

        try {
            PushNotifications::enroll();
            Setting::put(self::PUSH_ENROLLED_KEY, '1');
        } catch (Throwable $e) {
            Log::info('Push enroll skipped: '.$e->getMessage());
        }
    }
}
