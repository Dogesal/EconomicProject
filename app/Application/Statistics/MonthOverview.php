<?php

namespace App\Application\Statistics;

use App\Data\MoneyData;
use App\Data\MonthOverviewData;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Carbon;

/**
 * Headline numbers for a month: totals, comparison against the previous
 * month, average daily spending and a month-end projection. The projection
 * only applies to the current month; past and future months return null.
 */
class MonthOverview
{
    public function handle(int $year, int $month, string $currency): MonthOverviewData
    {
        $target = Carbon::create($year, $month, 1);
        $previousMonth = $target->copy()->subMonth();

        $current = $this->sums($year, $month, $currency);
        $previous = $this->sums((int) $previousMonth->year, (int) $previousMonth->month, $currency);

        $daysInMonth = (int) $target->daysInMonth;
        $isCurrentMonth = (int) now()->year === $year && (int) now()->month === $month;
        $daysElapsed = $isCurrentMonth ? max(1, (int) now()->day) : $daysInMonth;

        $averageDaily = (int) round($current['expense'] / $daysElapsed);

        $changePercentage = $previous['expense'] > 0
            ? round((($current['expense'] - $previous['expense']) / $previous['expense']) * 100, 1)
            : null;

        return new MonthOverviewData(
            totalExpense: MoneyData::fromMoney(Money::fromMinor($current['expense'], $currency)),
            totalIncome: MoneyData::fromMoney(Money::fromMinor($current['income'], $currency)),
            previousMonthExpense: MoneyData::fromMoney(Money::fromMinor($previous['expense'], $currency)),
            changePercentage: $changePercentage,
            averageDailyExpense: MoneyData::fromMoney(Money::fromMinor($averageDaily, $currency)),
            projectedExpense: $isCurrentMonth
                ? MoneyData::fromMoney(Money::fromMinor($averageDaily * $daysInMonth, $currency))
                : null,
            isCurrentMonth: $isCurrentMonth,
            daysElapsed: $daysElapsed,
            daysInMonth: $daysInMonth,
        );
    }

    /**
     * @return array{income: int, expense: int}
     */
    private function sums(int $year, int $month, string $currency): array
    {
        $row = Transaction::query()
            ->where('currency', $currency)
            ->forMonth($year, $month)
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->first();

        return [
            'income' => (int) ($row->income ?? 0),
            'expense' => (int) ($row->expense ?? 0),
        ];
    }
}
