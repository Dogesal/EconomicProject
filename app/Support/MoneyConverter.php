<?php

namespace App\Support;

use App\Domain\Models\ExchangeRate;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Converts money between currencies using the most recent stored exchange rate
 * on or before a given date. Falls back to the inverse rate when only the
 * opposite direction is available.
 */
class MoneyConverter
{
    public function convert(Money $money, string $toCurrency, ?CarbonInterface $asOf = null): Money
    {
        $toCurrency = strtoupper($toCurrency);

        if ($money->currency === $toCurrency) {
            return $money;
        }

        $rate = $this->rate($money->currency, $toCurrency, $asOf);

        if ($rate === null) {
            throw new RuntimeException("No exchange rate for {$money->currency} -> {$toCurrency}.");
        }

        return Money::fromDecimal($money->toDecimal() * $rate, $toCurrency);
    }

    public function tryConvert(Money $money, string $toCurrency, ?CarbonInterface $asOf = null): ?Money
    {
        try {
            return $this->convert($money, $toCurrency, $asOf);
        } catch (RuntimeException) {
            return null;
        }
    }

    public function rate(string $from, string $to, ?CarbonInterface $asOf = null): ?float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        $date = ($asOf ?? now())->toDateString();

        $direct = ExchangeRate::query()
            ->where('base_currency', $from)
            ->where('quote_currency', $to)
            ->whereDate('effective_on', '<=', $date)
            ->orderByDesc('effective_on')
            ->value('rate');

        if ($direct !== null) {
            return (float) $direct;
        }

        $inverse = ExchangeRate::query()
            ->where('base_currency', $to)
            ->where('quote_currency', $from)
            ->whereDate('effective_on', '<=', $date)
            ->orderByDesc('effective_on')
            ->value('rate');

        return $inverse !== null && (float) $inverse != 0.0 ? 1 / (float) $inverse : null;
    }
}
