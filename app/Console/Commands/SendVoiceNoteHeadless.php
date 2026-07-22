<?php

namespace App\Console\Commands;

use App\Application\Voice\SendVoiceNote;
use Illuminate\Console\Command;

/**
 * Nota de voz para el runtime PHP efímero que el widget lanza con la app
 * cerrada (VoiceCaptureActivity.kt). El texto llega en base64 para
 * sobrevivir tildes, comillas y espacios en la línea de comandos. Imprime
 * una línea JSON {"reply":"...","applied":N,"ok":bool} que el lado nativo
 * parsea para armar la notificación de resultado.
 */
class SendVoiceNoteHeadless extends Command
{
    protected $signature = 'voice:send-headless {--text-base64=}';

    protected $description = 'Envía una nota de voz transcrita al servidor y aplica el movimiento (invocado por el widget)';

    public function handle(SendVoiceNote $sendVoiceNote): int
    {
        $decoded = base64_decode((string) $this->option('text-base64'), true);

        $result = $sendVoiceNote->handle($decoded === false ? '' : $decoded);

        $this->line((string) json_encode($result, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
