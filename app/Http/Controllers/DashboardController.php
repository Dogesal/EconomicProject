<?php

namespace App\Http\Controllers;

use App\Application\Recurring\GenerateDueRecurringTransactions;
use App\Data\AccountData;
use App\Data\MoneyData;
use App\Data\TransactionData;
use App\Domain\ValueObjects\Money;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use App\Infrastructure\Repositories\Contracts\TransactionRepository;
use App\Support\DisplayCurrency;
use App\Support\MoneyConverter;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        AccountRepository $accounts,
        TransactionRepository $transactions,
        GenerateDueRecurringTransactions $recurring,
        DisplayCurrency $displayCurrency,
        MoneyConverter $converter,
    ): Response {
        // No always-on scheduler on-device: catch up recurring transactions on open.
        $recurring->handle();

        $totals = $accounts->totalsByCurrency();
        $display = $displayCurrency->resolve();

        return Inertia::render('Dashboard', [
            'displayCurrency' => $display,
            'totals' => $totals->map(fn ($money) => MoneyData::fromMoney($money))->values(),
            'convertedTotal' => MoneyData::fromMoney($this->convertedTotal($totals, $display, $converter)),
            'accounts' => AccountData::collect($accounts->allActive()),
            'recentTransactions' => TransactionData::collect($transactions->recent()),
        ]);
    }

    /**
     * Sum every currency's total into the display currency, skipping any
     * currency that has no available exchange rate.
     *
     * @param  Collection<string, Money>  $totals
     */
    private function convertedTotal(Collection $totals, string $display, MoneyConverter $converter): Money
    {
        return $totals->reduce(function (Money $carry, Money $money) use ($display, $converter) {
            $converted = $converter->tryConvert($money, $display);

            return $converted ? $carry->plus($converted) : $carry;
        }, Money::zero($display));
    }
}
