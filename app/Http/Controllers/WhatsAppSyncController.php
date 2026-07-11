<?php

namespace App\Http\Controllers;

use App\Application\WhatsApp\SyncWhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sincroniza los movimientos de WhatsApp desde cualquier pantalla (el
 * frontend lo dispara al abrir la app, al volver del background y cuando
 * el push FCM llega con la app abierta — este último con force=1). El
 * throttle vive en SyncWhatsApp.
 */
class WhatsAppSyncController extends Controller
{
    public function __invoke(Request $request, SyncWhatsApp $sync): RedirectResponse
    {
        $sync->handle($request->boolean('force'));

        return back();
    }
}
