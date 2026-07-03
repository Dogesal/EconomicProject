<?php

namespace App\Application\Debts;

use App\Data\MoneyData;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Models\Debt;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Collection;

/**
 * Outstanding (remaining) totals per direction, grouped by currency, plus
 * how many active debts are past their due date.
 */
class SummarizeOutstandingDebts
{
    /**
     * @param  Collection<int, Debt>  $debts
     * @return array{iOwe: Collection<int, MoneyData>, owedToMe: Collection<int, MoneyData>, overdueCount: int}
     */
    public function handle(Collection $debts): array
    {
        $totals = fn (DebtDirection $direction): Collection => $debts
            ->filter(fn (Debt $debt) => $debt->status === DebtStatus::Active && $debt->direction === $direction)
            ->groupBy('currency')
            ->map(fn (Collection $group, string $currency) => MoneyData::fromMoney(
                Money::fromMinor($group->sum(fn (Debt $debt) => $debt->remaining()->minorUnits), $currency)
            ))
            ->values();

        return [
            'iOwe' => $totals(DebtDirection::IOwe),
            'owedToMe' => $totals(DebtDirection::OwedToMe),
            'overdueCount' => $debts
                ->filter(fn (Debt $debt) => $debt->status === DebtStatus::Active && $debt->isOverdue())
                ->count(),
        ];
    }
}
