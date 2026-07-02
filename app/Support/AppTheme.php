<?php

namespace App\Support;

use App\Domain\Models\Setting;

/**
 * Resolves the UI theme preference. `system` (the default) follows the
 * device's dark mode via prefers-color-scheme on the client; `light` and
 * `dark` are explicit user overrides from the settings screen.
 */
class AppTheme
{
    public const SETTING_KEY = 'theme';

    public const OPTIONS = ['system', 'light', 'dark'];

    public function resolve(): string
    {
        $configured = Setting::get(self::SETTING_KEY);

        return in_array($configured, self::OPTIONS, true) ? $configured : 'system';
    }
}
