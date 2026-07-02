<?php

namespace App\Application\Transactions;

use App\Domain\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a transaction. When the transaction is one leg of a transfer, both
 * legs are removed so the two accounts stay balanced.
 */
class DeleteTransaction
{
    public function handle(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            if ($transaction->transfer_group_id !== null) {
                Transaction::query()
                    ->where('transfer_group_id', $transaction->transfer_group_id)
                    ->get()
                    ->each->delete();

                return;
            }

            $transaction->delete();
        });
    }
}
