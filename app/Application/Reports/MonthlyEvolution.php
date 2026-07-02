<?php

namespace App\Application\Reports;

use App\Data\MoneyData;
use App\Data\MonthlyEvolutionPointData;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Income vs expense totals per month for the last N months and a given
 * currency. Targets SQLite (uses strftime), which is the on-device engine.
 */
class MonthlyEvolution
{
    private const MONTHS = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /**
     * @return Collection<int, MonthlyEvolutionPointData>
     */
    public function handle(string $currency, int $monthsBack = 6): Collection
    {
        $start = Carbon::now()->startOfMonth()->subMonths($monthsBack - 1);

        $rows = Transaction::query()
            ->where('currency', $currency)
            ->whereDate('occurred_on', '>=', $start->toDateString())
            ->selectRaw("strftime('%Y', occurred_on) as y, strftime('%m', occurred_on) as m")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($row) => "{$row->y}-{$row->m}");

        return collect(range(0, $monthsBack - 1))->map(function (int $offset) use ($start, $rows, $currency) {
            $date = (clone $start)->addMonths($offset);
            $key = $date->format('Y-m');
            $row = $rows->get($key);

            $income = Money::fromMinor((int) ($row->income ?? 0), $currency);
            $expense = Money::fromMinor((int) ($row->expense ?? 0), $currency);

            return new MonthlyEvolutionPointData(
                year: (int) $date->year,
                month: (int) $date->month,
                label: self::MONTHS[$date->month],
                income: MoneyData::fromMoney($income),
                expense: MoneyData::fromMoney($expense),
                net: MoneyData::fromMoney($income->minus($expense)),
            );
        });
    }
}
