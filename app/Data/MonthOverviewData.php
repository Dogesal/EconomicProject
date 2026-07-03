<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * Headline numbers for the Statistics screen: month totals, comparison
 * with the previous month, daily average and month-end projection.
 */
class MonthOverviewData extends Data
{
    public function __construct(
        public MoneyData $totalExpense,
        public MoneyData $totalIncome,
        public MoneyData $previousMonthExpense,
        public ?float $changePercentage,
        public MoneyData $averageDailyExpense,
        public ?MoneyData $projectedExpense,
        public bool $isCurrentMonth,
        public int $daysElapsed,
        public int $daysInMonth,
    ) {}
}
