<?php

namespace Economia\MobileBiometrics;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the biometrics NativePHP plugin. All the real work happens on
 * the native side (resources/android/src/BiometricFunctions.kt), wired by
 * the bridge function declared in nativephp.json; the result comes back as
 * the core Native\Mobile\Events\Biometric\Completed event.
 */
class MobileBiometricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
