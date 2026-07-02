<?php

namespace App\Application\Reports;

use App\Data\CategorySpendingData;
use App\Data\MoneyData;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Collection;

/**
 * Expense totals grouped by category for a month and currency, ordered by
 * amount descending, with each category's share of the total.
 */
class SpendingByCategory
{
    /**
     * @return Collection<int, CategorySpendingData>
     */
    public function handle(int $year, int $month, string $currency): Collection
    {
        $rows = Transaction::query()
            ->where('type', TransactionType::Expense)
            ->where('currency', $currency)
            ->forMonth($year, $month)
            ->whereNotNull('category_id')
            ->with('category:id,name,color')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get();

        $grandTotal = (int) $rows->sum('total');

        return $rows->map(function ($row) use ($currency, $grandTotal) {
            $total = (int) $row->total;

            return new CategorySpendingData(
                categoryId: $row->category_id,
                categoryName: $row->category?->name ?? 'Sin categoría',
                color: $row->category?->color,
                total: MoneyData::fromMoney(Money::fromMinor($total, $currency)),
                percentage: $grandTotal > 0 ? round(($total / $grandTotal) * 100, 1) : 0.0,
            );
        })->values();
    }
}
