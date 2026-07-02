<?php

namespace App\Data;

use App\Domain\Models\Budget;
use App\Domain\ValueObjects\Money;
use Spatie\LaravelData\Data;

class BudgetConsumptionData extends Data
{
    public function __construct(
        public string $budgetId,
        public CategorySummaryData $category,
        public MoneyData $budgeted,
        public MoneyData $spent,
        public MoneyData $remaining,
        public float $percentage,
        public bool $isOverBudget,
    ) {}

    public static function build(Budget $budget, Money $spent): self
    {
        $budgeted = $budget->amount;
        $remaining = $budgeted->minus($spent);
        $percentage = $budgeted->minorUnits > 0
            ? round(($spent->minorUnits / $budgeted->minorUnits) * 100, 1)
            : 0.0;

        return new self(
            budgetId: $budget->id,
            category: CategorySummaryData::fromModel($budget->category),
            budgeted: MoneyData::fromMoney($budgeted),
            spent: MoneyData::fromMoney($spent),
            remaining: MoneyData::fromMoney($remaining),
            percentage: $percentage,
            isOverBudget: $spent->minorUnits > $budgeted->minorUnits,
        );
    }
}
