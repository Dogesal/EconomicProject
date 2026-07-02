<?php

namespace App\Providers;

use Economia\MobileBiometrics\MobileBiometricsServiceProvider;
use Illuminate\Support\ServiceProvider;

class NativeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
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
        ];
    }
}
