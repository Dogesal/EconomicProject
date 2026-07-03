<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;

/**
 * Expenses of a single category for a month, used by the drill-down
 * bottom sheet on the Budgets and Reports screens.
 */
class CategoryExpensesData extends Data
{
    /**
     * @param  Collection<int, TransactionData>  $transactions
     */
    public function __construct(
        public CategorySummaryData $category,
        public int $year,
        public int $month,
        public MoneyData $total,
        public int $count,
        #[DataCollectionOf(TransactionData::class)]
        public Collection $transactions,
    ) {}
}
