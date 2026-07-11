<?php

namespace App\Http\Controllers;

use App\Application\Budgets\CalculateBudgetConsumption;
use App\Application\Debts\SummarizeOutstandingDebts;
use App\Application\Recurring\GenerateDueRecurringTransactions;
use App\Application\Reports\MonthlyEvolution;
use App\Application\Reports\SpendingByCategory;
use App\Application\WhatsApp\ApplyPendingMessages;
use App\Data\AccountData;
use App\Data\GoalData;
use App\Data\MoneyData;
use App\Data\RecurringTransactionData;
use App\Data\TransactionData;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Enums\GoalStatus;
use App\Domain\Models\Debt;
use App\Domain\Models\RecurringTransaction;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use App\Infrastructure\Repositories\Contracts\TransactionRepository;
use App\Support\DisplayCurrency;
use App\Support\MoneyConverter;
use App\Support\ReminderScheduler;
use App\Support\WhatsAppSyncNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class DashboardController extends Controller
{
    public function __invoke(
        AccountRepository $accounts,
        TransactionRepository $transactions,
        GenerateDueRecurringTransactions $recurring,
        DisplayCurrency $displayCurrency,
        MoneyConverter $converter,
        ReminderScheduler $reminders,
        CalculateBudgetConsumption $consumption,
        MonthlyEvolution $evolution,
        SpendingByCategory $spending,
        SummarizeOutstandingDebts $summarizeDebts,
        ApplyPendingMessages $whatsAppSync,
        WhatsAppSyncNotifier $whatsAppNotifier,
    ): Response {
        // No always-on scheduler on-device: catch up recurring transactions on open.
        $recurring->handle();

        // Re-sync local notification reminders; never let it break the dashboard.
        try {
            $reminders->schedule();
        } catch (Throwable $e) {
            Log::warning('Reminder scheduling failed: '.$e->getMessage());
        }

        // Aplicar movimientos enviados por WhatsApp; nunca debe romper el dashboard.
        try {
            $whatsAppResult = $whatsAppSync->handle();

            if ($whatsAppResult->hasChanges()) {
                $summary = $this->whatsAppSummary($whatsAppResult->applied, $whatsAppResult->failed);
                session()->flash('success', $summary);
                $whatsAppNotifier->notify($summary);
            }
        } catch (Throwable $e) {
            Log::warning('WhatsApp sync failed: '.$e->getMessage());
        }

        $totals = $accounts->totalsByCurrency();
        $display = $displayCurrency->resolve();
        $year = (int) now()->year;
        $month = (int) now()->month;

        $convertedTotal = $this->convertedTotal($totals, $display, $converter);
        $activeDebts = Debt::where('status', DebtStatus::Active)->get();

        return Inertia::render('Dashboard', [
            'displayCurrency' => $display,
            'totals' => $totals->map(fn ($money) => MoneyData::fromMoney($money))->values(),
            'convertedTotal' => MoneyData::fromMoney($convertedTotal),
            'netBalance' => $this->netBalance($convertedTotal, $activeDebts, $display, $converter),
            'accounts' => AccountData::collect($accounts->allActive()),
            'recentTransactions' => TransactionData::collect($transactions->recent(5)),
            'monthSummary' => $evolution->handle($display, monthsBack: 1)->first(),
            'topSpending' => $spending->handle($year, $month, $display)->take(3)->values(),
            'budgets' => $consumption->handle($year, $month)
                ->sortByDesc('percentage')
                ->take(4)
                ->values(),
            'goals' => GoalData::collect(
                SavingsGoal::with('account')
                    ->where('status', GoalStatus::Active)
                    ->orderByDesc('created_at')
                    ->take(3)
                    ->get()
            ),
            'debtSummary' => $summarizeDebts->handle($activeDebts),
            'upcomingRecurring' => RecurringTransactionData::collect(
                RecurringTransaction::with('account')
                    ->where(fn (Builder $query) => $query
                        ->whereNull('end_on')
                        ->orWhereColumn('next_run_on', '<=', 'end_on'))
                    ->orderBy('next_run_on')
                    ->take(3)
                    ->get()
            ),
        ]);
    }

    private function whatsAppSummary(int $applied, int $failed): string
    {
        $parts = [];

        if ($applied > 0) {
            $parts[] = $applied === 1
                ? 'Se registró 1 movimiento de WhatsApp'
                : "Se registraron {$applied} movimientos de WhatsApp";
        }

        if ($failed > 0) {
            $parts[] = $failed === 1 ? '1 falló (revisa Ajustes)' : "{$failed} fallaron (revisa Ajustes)";
        }

        return implode('; ', $parts).'.';
    }

    /**
     * Money vs. debt breakdown for the dashboard bar: total money on hand,
     * outstanding "I owe" debt and the resulting net, all in the display
     * currency (currencies without an exchange rate are skipped, same as the
     * converted total). Null when nothing is owed, so the card can hide.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array{available: MoneyData, debts: MoneyData, net: MoneyData}|null
     */
    private function netBalance(Money $available, Collection $debts, string $display, MoneyConverter $converter): ?array
    {
        $owed = $debts
            ->filter(fn (Debt $debt) => $debt->direction === DebtDirection::IOwe)
            ->reduce(function (Money $carry, Debt $debt) use ($display, $converter) {
                $converted = $converter->tryConvert($debt->remaining(), $display);

                return $converted ? $carry->plus($converted) : $carry;
            }, Money::zero($display));

        if ($owed->isZero()) {
            return null;
        }

        return [
            'available' => MoneyData::fromMoney($available),
            'debts' => MoneyData::fromMoney($owed),
            'net' => MoneyData::fromMoney($available->minus($owed)),
        ];
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
