<?php

namespace App\Http\Controllers;

use App\Application\WhatsApp\AccountsSnapshot;
use App\Infrastructure\Http\WebhookServerClient;
use App\Support\WhatsAppLink;
use Illuminate\Http\RedirectResponse;
use Native\Mobile\Facades\PushNotifications;
use Throwable;

class WhatsAppSettingsController extends Controller
{
    public function __construct(
        private readonly WhatsAppLink $link,
        private readonly WebhookServerClient $client,
    ) {}

    /**
     * Registra el dispositivo (si hace falta) y genera un código de
     * vinculación que el usuario envía al bot por WhatsApp.
     */
    public function link(): RedirectResponse
    {
        if (! $this->link->isConfigured()) {
            return back()->with('error', 'El servidor de WhatsApp no está configurado en esta versión de la app.');
        }

        try {
            if ($this->link->apiToken() === null) {
                $registration = $this->client->register('Mi Economía Android');
                $this->link->storeDevice($registration['device_id'], $registration['api_token']);
            }

            $linkCode = $this->client->requestLinkCode();
        } catch (Throwable) {
            return back()->with('error', 'No se pudo contactar al servidor. Revisa tu conexión e inténtalo de nuevo.');
        }

        $this->link->storeBotPhone($linkCode['bot_phone'] ?? null);

        return back()->with('whatsappLinkCode', [
            'code' => $linkCode['code'],
            'expires_at' => $linkCode['expires_at'],
            'bot_phone' => $linkCode['bot_phone'],
        ]);
    }

    /**
     * Consulta si el usuario ya envió el código ("Ya lo envié").
     */
    public function refresh(): RedirectResponse
    {
        try {
            $status = $this->client->deviceStatus();
        } catch (Throwable) {
            return back()->with('error', 'No se pudo contactar al servidor. Revisa tu conexión e inténtalo de nuevo.');
        }

        if (! ($status['linked'] ?? false)) {
            return back()->with('error', 'Aún no recibimos tu código. Envíalo por WhatsApp y vuelve a intentar.');
        }

        $this->link->markLinked(true);

        // El bot necesita las cuentas desde el primer mensaje: subir el
        // snapshot inicial sin esperar al próximo sync del dashboard.
        try {
            $this->client->syncAccounts(AccountsSnapshot::build());
        } catch (Throwable) {
            // El sync del dashboard lo reintentará.
        }

        // En el dispositivo, pedir permiso de notificaciones y generar el
        // token FCM; StorePushToken lo sube cuando llega TokenGenerated.
        if (function_exists('nativephp_call')) {
            PushNotifications::enroll();
        }

        return back()->with('success', 'WhatsApp vinculado ('.($status['phone_masked'] ?? '').').');
    }

    public function unlink(): RedirectResponse
    {
        try {
            $this->client->unlink();
        } catch (Throwable) {
            // Aunque el servidor no responda, se desvincula localmente:
            // el bot rechazará mensajes del teléfono en cuanto haya red.
        }

        $this->link->clear();

        return back()->with('success', 'WhatsApp desvinculado.');
    }
}
