<?php

namespace Economia\WhatsAppBackgroundSync;

use Illuminate\Support\ServiceProvider;

/**
 * Registra el plugin de sync en background. Todo el trabajo ocurre en el
 * lado nativo: resources/android/src/WhatsAppSyncMessagingService.kt recibe
 * el push FCM de datos con la app cerrada, arranca un runtime PHP efímero y
 * corre `whatsapp:sync-headless`; el AndroidManifest.xml del plugin (merge
 * automático del compilador de plugins) registra el service.
 */
class WhatsAppBackgroundSyncServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
}
