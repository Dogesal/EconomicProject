<?php

namespace App\Application\Transactions;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\Category;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Updates an income or expense transaction. Transfers are edited via delete +
 * recreate to keep both legs consistent.
 */
class UpdateTransaction
{
    public function handle(
        Transaction $transaction,
        Money $amount,
        ?Category $category = null,
        ?string $description = null,
        ?CarbonInterface $occurredOn = null,
    ): Transaction {
        if ($transaction->type === TransactionType::Transfer) {
            throw new InvalidArgumentException('Transfers cannot be edited in place.');
        }

        if ($amount->currency !== $transaction->currency) {
            throw new InvalidArgumentException('Transaction currency cannot change.');
        }

        $transaction->update([
            'amount' => $amount->absolute(),
            'category_id' => $category?->id,
            'description' => $description,
            'occurred_on' => $occurredOn ?? $transaction->occurred_on,
        ]);

        return $transaction;
    }
}
