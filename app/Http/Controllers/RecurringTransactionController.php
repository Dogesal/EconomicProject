<?php

namespace App\Http\Controllers;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\RecurringTransaction;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\StoreRecurringTransactionRequest;
use Illuminate\Http\RedirectResponse;

class RecurringTransactionController extends Controller
{
    public function store(StoreRecurringTransactionRequest $request): RedirectResponse
    {
        $account = Account::findOrFail($request->string('account_id'));
        $amount = Money::fromDecimal($request->input('amount'), $account->currency);

        RecurringTransaction::create([
            'account_id' => $account->id,
            'category_id' => $request->input('category_id'),
            'type' => $request->enum('type', TransactionType::class),
            'amount' => $amount,
            'currency' => $account->currency,
            'description' => $request->input('description'),
            'frequency' => $request->string('frequency'),
            'interval' => $request->integer('interval'),
            'next_run_on' => $request->date('next_run_on'),
            'end_on' => $request->date('end_on'),
        ]);

        return back()->with('success', 'Transacción recurrente creada.');
    }

    public function destroy(RecurringTransaction $recurringTransaction): RedirectResponse
    {
        $recurringTransaction->delete();

        return back()->with('success', 'Recurrente eliminada.');
    }
}
