<?php

namespace App\Application\Budgets;

use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;

/**
 * Creates or replaces the budget for a category in a given month (one budget
 * per category per period, enforced by a unique key).
 */
class CreateBudget
{
    public function handle(Category $category, int $year, int $month, Money $amount): Budget
    {
        $budget = Budget::withTrashed()->updateOrCreate(
            [
                'category_id' => $category->id,
                'period_year' => $year,
                'period_month' => $month,
            ],
            [
                'amount' => $amount,
                'currency' => $amount->currency,
            ],
        );

        if ($budget->trashed()) {
            $budget->restore();
        }

        return $budget;
    }
}
