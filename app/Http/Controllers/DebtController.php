<?php

namespace App\Http\Controllers;

use App\Application\Debts\CreateDebt;
use App\Application\Debts\RecordDebtPayment;
use App\Data\AccountData;
use App\Data\DebtData;
use App\Data\MoneyData;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Models\Account;
use App\Domain\Models\Debt;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\PayDebtRequest;
use App\Http\Requests\StoreDebtRequest;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DebtController extends Controller
{
    public function index(AccountRepository $accounts): Response
    {
        $debts = Debt::query()
            ->orderByRaw("case when status = 'active' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('Debts/Index', [
            'debts' => DebtData::collect($debts),
            'summary' => $this->summarize($debts),
            'accounts' => AccountData::collect($accounts->allActive()),
        ]);
    }

    public function store(StoreDebtRequest $request, CreateDebt $create): RedirectResponse
    {
        $currency = strtoupper($request->string('currency'));

        $create->handle(
            name: $request->string('name'),
            direction: $request->enum('direction', DebtDirection::class),
            amount: Money::fromDecimal($request->input('amount'), $currency),
            dueDate: $request->date('due_date'),
        );

        return back()->with('success', 'Deuda registrada.');
    }

    public function pay(PayDebtRequest $request, Debt $debt, RecordDebtPayment $payment): RedirectResponse
    {
        $account = Account::findOrFail($request->string('account_id'));

        $payment->handle(
            debt: $debt,
            account: $account,
            amount: Money::fromDecimal($request->input('amount'), $debt->currency),
            occurredOn: $request->date('occurred_on'),
        );

        return back()->with('success', $debt->direction === DebtDirection::IOwe ? 'Pago registrado.' : 'Cobro registrado.');
    }

    public function destroy(Debt $debt): RedirectResponse
    {
        $debt->delete();

        return back()->with('success', 'Deuda eliminada.');
    }

    /**
     * Outstanding (remaining) totals per direction, grouped by currency.
     *
     * @param  Collection<int, Debt>  $debts
     * @return array<string, Collection<int, MoneyData>>
     */
    private function summarize(Collection $debts): array
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
        ];
    }
}
