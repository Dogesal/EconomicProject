<?php

namespace App\Support;

use App\Domain\Models\Setting;
use Illuminate\Http\Request;

/**
 * Session-based app lock. When enabled, the app is locked on each fresh launch
 * until the user passes the biometric prompt (or the web fallback). This is a
 * privacy gate for an on-device single-user app, not a cryptographic boundary.
 */
class AppLock
{
    public const SETTING_KEY = 'app_lock_enabled';

    private const SESSION_KEY = 'app_unlocked';

    public function isEnabled(): bool
    {
        return Setting::get(self::SETTING_KEY) === '1';
    }

    public function setEnabled(bool $enabled): void
    {
        Setting::put(self::SETTING_KEY, $enabled ? '1' : '0');
    }

    public function isUnlocked(Request $request): bool
    {
        return $request->session()->get(self::SESSION_KEY) === true;
    }

    public function unlock(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, true);
    }

    public function lock(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }
}
