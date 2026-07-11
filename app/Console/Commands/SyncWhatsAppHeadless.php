<?php

namespace App\Console\Commands;

use App\Application\WhatsApp\ApplyPendingMessages;
use App\Support\WhatsAppLink;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sync de WhatsApp para el runtime PHP efímero que el push FCM despierta
 * con la app cerrada (WhatsAppSyncMessagingService.kt). Sin sesión ni
 * throttle: el push significa que hay un mensaje nuevo esperando. Imprime
 * una línea JSON {"applied":N,"failed":N} que el lado nativo parsea para
 * armar la notificación de resultado.
 */
class SyncWhatsAppHeadless extends Command
{
    protected $signature = 'whatsapp:sync-headless';

    protected $description = 'Aplica los movimientos pendientes de WhatsApp (invocado por el push FCM en background)';

    public function handle(ApplyPendingMessages $applyPendingMessages, WhatsAppLink $link): int
    {
        if (! $link->isConfigured() || ! $link->isLinked()) {
            $this->line(json_encode(['applied' => 0, 'failed' => 0]));

            return self::SUCCESS;
        }

        try {
            $result = $applyPendingMessages->handle();
            $this->line(json_encode(['applied' => $result->applied, 'failed' => $result->failed]));
        } catch (Throwable $e) {
            $this->line(json_encode(['applied' => 0, 'failed' => 0, 'error' => $e->getMessage()]));
        }

        return self::SUCCESS;
    }
}
