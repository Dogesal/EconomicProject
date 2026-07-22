<?php

namespace App\Http\Controllers;

use App\Application\Voice\SendVoiceNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Camino del widget de voz con la app abierta: VoiceCaptureActivity delega
 * en window.__sendVoiceNote y el reply del servidor se muestra como flash
 * (el movimiento ya quedó aplicado dentro de SendVoiceNote).
 */
class VoiceNoteController extends Controller
{
    public function __invoke(Request $request, SendVoiceNote $sendVoiceNote): RedirectResponse
    {
        $validated = $request->validate(['text' => ['required', 'string', 'max:500']]);

        $result = $sendVoiceNote->handle($validated['text']);

        return back()->with($result['ok'] ? 'success' : 'error', $result['reply']);
    }
}
