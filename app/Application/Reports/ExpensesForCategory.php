<?php

namespace App\Application\Reports;

use App\Data\CategoryExpensesData;
use App\Data\CategorySummaryData;
use App\Data\MoneyData;
use App\Data\TransactionData;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Category;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;

/**
 * Expenses of one category for a month in the display currency (same
 * semantics as SpendingByCategory, which feeds the lists this drills into).
 */
class ExpensesForCategory
{
    public function handle(string $categoryId, int $year, int $month, string $currency): ?CategoryExpensesData
    {
        $category = Category::find($categoryId);

        if ($category === null) {
            return null;
        }

        $transactions = Transaction::query()
            ->where('type', TransactionType::Expense)
            ->where('currency', $currency)
            ->where('category_id', $category->id)
            ->forMonth($year, $month)
            ->with(['account', 'category'])
            ->orderByDesc('occurred_on')
            ->orderByDesc('created_at')
            ->get();

        $total = Money::fromMinor((int) $transactions->sum(fn (Transaction $t) => $t->amount->minorUnits), $currency);

        return new CategoryExpensesData(
            category: CategorySummaryData::fromModel($category),
            year: $year,
            month: $month,
            total: MoneyData::fromMoney($total),
            count: $transactions->count(),
            transactions: TransactionData::collect($transactions),
        );
    }
}
