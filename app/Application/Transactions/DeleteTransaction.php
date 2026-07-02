<?php

namespace App\Application\Transactions;

use App\Domain\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Deletes a transaction. When the transaction is one leg of a transfer, both
 * legs are removed so the two accounts stay balanced. Deleting an inflow is
 * rejected when the account already spent that money (negative balance).
 */
class DeleteTransaction
{
    public function handle(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            if ($transaction->transfer_group_id !== null) {
                $legs = Transaction::query()
                    ->where('transfer_group_id', $transaction->transfer_group_id)
                    ->get();

                $legs->each(fn (Transaction $leg) => $this->guardAgainstNegativeBalance($leg));
                $legs->each->delete();

                return;
            }

            $this->guardAgainstNegativeBalance($transaction);

            $transaction->delete();
        });
    }

    private function guardAgainstNegativeBalance(Transaction $transaction): void
    {
        if (! $transaction->is_inflow || $transaction->account === null) {
            return;
        }

        if ($transaction->account->current_balance->minorUnits - $transaction->amount->minorUnits < 0) {
            throw new InvalidArgumentException(
                'No se puede eliminar: la cuenta quedaría en negativo porque ya gastaste parte de este ingreso.'
            );
        }
    }
}
