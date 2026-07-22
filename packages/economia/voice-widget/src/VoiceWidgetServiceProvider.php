<?php

namespace Economia\VoiceWidget;

use Illuminate\Support\ServiceProvider;

/**
 * Registra el plugin del widget de voz. Todo el trabajo ocurre en el lado
 * nativo: VoiceWidgetProvider.kt pinta el widget de pantalla de inicio y
 * VoiceCaptureActivity.kt transcribe con RecognizerIntent y entrega el texto
 * a la app (webview si está viva, `voice:send-headless` en un runtime PHP
 * efímero si está cerrada). El AndroidManifest.xml del plugin (merge
 * automático) registra el receiver y la activity; los layouts viajan como
 * assets declarados en nativephp.json.
 */
class VoiceWidgetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
}
