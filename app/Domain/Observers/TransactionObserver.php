<?php

namespace App\Domain\Observers;

use App\Domain\Models\Account;
use App\Domain\Models\Transaction;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        $transaction->account?->recalculateBalance();
    }

    public function updated(Transaction $transaction): void
    {
        // If the transaction moved to another account, recompute both.
        if ($transaction->wasChanged('account_id')) {
            $original = $transaction->getOriginal('account_id');

            if ($original !== null) {
                Account::query()->whereKey($original)->first()?->recalculateBalance();
            }
        }

        $transaction->account?->recalculateBalance();
    }

    public function deleted(Transaction $transaction): void
    {
        $transaction->account?->recalculateBalance();

        // Deleting a debt payment reopens the corresponding portion of the debt.
        if ($transaction->debt_id !== null) {
            $transaction->debt?->revertPayment($transaction->amount);
        }
    }

    public function restored(Transaction $transaction): void
    {
        $transaction->account?->recalculateBalance();

        if ($transaction->debt_id !== null) {
            $transaction->debt?->applyPayment($transaction->amount);
        }
    }
}
