<?php

namespace App\Application\Statistics;

use App\Application\Reports\SpendingByCategory;
use App\Data\MoneyData;
use App\Data\SpendingHabitsData;
use App\Data\TransactionData;
use App\Data\WeekdaySpendingData;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;

/**
 * Spending habits for a month and currency: the five largest expenses,
 * the dominant category and the weekday with the highest accumulated
 * spending. Targets SQLite (uses strftime, %w: 0 = Sunday).
 */
class SpendingHabits
{
    private const WEEKDAYS = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

    public function __construct(private SpendingByCategory $spendingByCategory) {}

    public function handle(int $year, int $month, string $currency): SpendingHabitsData
    {
        $topExpenses = Transaction::query()
            ->where('type', TransactionType::Expense)
            ->where('currency', $currency)
            ->forMonth($year, $month)
            ->with(['account', 'category'])
            ->orderByDesc('amount')
            ->take(5)
            ->get();

        $weekdayRow = Transaction::query()
            ->where('type', TransactionType::Expense)
            ->where('currency', $currency)
            ->forMonth($year, $month)
            ->selectRaw("strftime('%w', occurred_on) as dow, SUM(amount) as total")
            ->groupBy('dow')
            ->orderByDesc('total')
            ->first();

        return new SpendingHabitsData(
            topExpenses: TransactionData::collect($topExpenses),
            dominantCategory: $this->spendingByCategory->handle($year, $month, $currency)->first(),
            topWeekday: $weekdayRow !== null
                ? new WeekdaySpendingData(
                    weekday: (int) $weekdayRow->dow,
                    label: self::WEEKDAYS[(int) $weekdayRow->dow],
                    total: MoneyData::fromMoney(Money::fromMinor((int) $weekdayRow->total, $currency)),
                )
                : null,
        );
    }
}
