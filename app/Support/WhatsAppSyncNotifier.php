<?php

namespace App\Support;

use Ikromjon\LocalNotifications\LocalNotifications;

/**
 * Muestra una notificación local con el resumen del sync de WhatsApp
 * (movimientos aplicados/fallidos), además del flash dentro de la app.
 * Las llamadas nativas no-opean fuera del runtime del dispositivo.
 */
class WhatsAppSyncNotifier
{
    public function __construct(private LocalNotifications $notifications) {}

    public function notify(string $summary): void
    {
        if (! function_exists('nativephp_call')) {
            return;
        }

        $this->notifications->requestPermission();

        $this->notifications->schedule([
            'id' => 'whatsapp-sync',
            'title' => 'Movimientos de WhatsApp',
            'body' => $summary,
            'delay' => 1,
        ]);
    }
}
