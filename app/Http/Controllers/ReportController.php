<?php

namespace App\Http\Controllers;

use App\Application\Reports\ExpensesForCategory;
use App\Application\Reports\MonthlyEvolution;
use App\Application\Reports\SpendingByCategory;
use App\Data\MoneyData;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use App\Support\DisplayCurrency;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(
        SpendingByCategory $spending,
        MonthlyEvolution $evolution,
        ExpensesForCategory $expenses,
        AccountRepository $accounts,
        DisplayCurrency $currency,
    ): Response {
        $year = (int) request()->integer('year', (int) now()->year);
        $month = (int) request()->integer('month', (int) now()->month);
        $displayCurrency = $currency->resolve();

        return Inertia::render('Reports/Index', [
            'period' => ['year' => $year, 'month' => $month],
            'currency' => $displayCurrency,
            'spendingByCategory' => $spending->handle($year, $month, $displayCurrency),
            'monthlyEvolution' => $evolution->handle($displayCurrency, 6),
            'netWorth' => $accounts->totalsByCurrency()
                ->map(fn ($money) => MoneyData::fromMoney($money))
                ->values(),
            'categoryExpenses' => Inertia::optional(fn () => request()->filled('drill_category')
                ? $expenses->handle(request()->string('drill_category')->toString(), $year, $month, $displayCurrency)
                : null),
        ]);
    }
}
