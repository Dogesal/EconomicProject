<?php

namespace App\Http\Controllers;

use App\Application\WhatsApp\SyncWhatsApp;
use Illuminate\Http\RedirectResponse;

/**
 * Sincroniza los movimientos de WhatsApp desde cualquier pantalla (el
 * frontend lo dispara al abrir la app y al volver del background). El
 * throttle vive en SyncWhatsApp.
 */
class WhatsAppSyncController extends Controller
{
    public function __invoke(SyncWhatsApp $sync): RedirectResponse
    {
        $sync->handle();

        return back();
    }
}
