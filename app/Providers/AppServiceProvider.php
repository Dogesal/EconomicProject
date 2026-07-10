<?php

namespace App\Providers;

use App\Listeners\StorePushToken;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\Events\PushNotification\TokenGenerated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(TokenGenerated::class, StorePushToken::class);
    }
}
