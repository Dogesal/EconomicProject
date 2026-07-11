<?php

namespace App\Providers;

use Codingwithrk\DoubleBackToClose\DoubleBackToCloseServiceProvider;
use Codingwithrk\DoubleBackToClose\Facades\DoubleBackToClose;
use Economia\MobileBiometrics\MobileBiometricsServiceProvider;
use Economia\WebViewFileChooser\WebViewFileChooserServiceProvider;
use Economia\WhatsAppBackgroundSync\WhatsAppBackgroundSyncServiceProvider;
use Ikromjon\LocalNotifications\LocalNotificationsServiceProvider;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Providers\BrowserServiceProvider;
use Native\Mobile\Providers\DialogServiceProvider;
use Native\Mobile\Providers\FileServiceProvider;
use Native\Mobile\Providers\ShareServiceProvider;
use S2BR\MobileSplashscreen\MobileSplashscreenServiceProvider;
use ZinXan\QuickActions\QuickActions;
use ZinXan\QuickActions\QuickActionsServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Long-press shortcuts on the app icon (synced to the device by the
        // quick-actions plugin; harmless no-op on web/tests).
        QuickActions::addItem('Nuevo gasto', 'nuevo-gasto')
            ->route('transactions.index', ['new' => 1]);

        QuickActions::addItem('Nueva deuda', 'nueva-deuda')
            ->route('debts.index', ['new' => 1]);

        // Require a second back press to leave the app (no-op off-device).
        DoubleBackToClose::enable('Tocá atrás de nuevo para salir', 2000);
    }

    /**
     * The NativePHP plugins to enable.
     *
     * Only plugins listed here will be compiled into your native builds.
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    public function plugins(): array
    {
        return [
            MobileBiometricsServiceProvider::class,
            LocalNotificationsServiceProvider::class,
            QuickActionsServiceProvider::class,
            ShareServiceProvider::class,
            FileServiceProvider::class,
            MobileSplashscreenServiceProvider::class,
            DoubleBackToCloseServiceProvider::class,
            DialogServiceProvider::class,
            BrowserServiceProvider::class,
            WebViewFileChooserServiceProvider::class,
            WhatsAppBackgroundSyncServiceProvider::class,

        ];
    }
}
