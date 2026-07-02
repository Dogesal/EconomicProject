<?php

namespace App\Support;

use App\Domain\Models\Account;
use App\Domain\Models\Setting;

/**
 * Resolves the currency used for reports, budgets and totals. Prefers the
 * user-configured `display_currency` setting, then the first active account's
 * currency, then a sane default.
 */
class DisplayCurrency
{
    public const SETTING_KEY = 'display_currency';

    public function resolve(): string
    {
        $configured = Setting::get(self::SETTING_KEY);

        if ($configured !== null && $configured !== '') {
            return strtoupper($configured);
        }

        return Account::query()
            ->where('is_archived', false)
            ->orderBy('created_at')
            ->value('currency') ?? 'PEN';
    }
}
