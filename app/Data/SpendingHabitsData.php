<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Spending habits for a month: largest expenses, dominant category and
 * the weekday with the highest accumulated spending.
 */
class SpendingHabitsData extends Data
{
    /**
     * @param  Collection<int, TransactionData>  $topExpenses
     */
    public function __construct(
        #[DataCollectionOf(TransactionData::class)]
        public Collection $topExpenses,
        public ?CategorySpendingData $dominantCategory,
        public ?WeekdaySpendingData $topWeekday,
    ) {}
}
