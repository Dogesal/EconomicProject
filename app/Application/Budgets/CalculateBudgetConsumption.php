<?php

namespace App\Application\Budgets;

use App\Data\BudgetConsumptionData;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Budget;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Collection;

/**
 * For a given month, returns each budget alongside how much has been spent in
 * its category, computed with a single grouped query (no N+1).
 */
class CalculateBudgetConsumption
{
    /**
     * @return Collection<int, BudgetConsumptionData>
     */
    public function handle(int $year, int $month): Collection
    {
        $budgets = Budget::query()
            ->with('category')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->get();

        if ($budgets->isEmpty()) {
            return collect();
        }

        $spentByCategory = Transaction::query()
            ->where('type', TransactionType::Expense)
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->forMonth($year, $month)
            ->selectRaw('category_id, currency, SUM(amount) as total')
            ->groupBy('category_id', 'currency')
            ->get()
            ->keyBy('category_id');

        return $budgets->map(function (Budget $budget) use ($spentByCategory) {
            $row = $spentByCategory->get($budget->category_id);
            $spent = Money::fromMinor((int) ($row->total ?? 0), $budget->currency);

            return BudgetConsumptionData::build($budget, $spent);
        })->values();
    }
}
