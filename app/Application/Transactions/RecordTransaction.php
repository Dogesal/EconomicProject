<?php

namespace App\Application\Transactions;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Records a single income or expense against an account. Transfers must use
 * {@see TransferBetweenAccounts} instead.
 */
class RecordTransaction
{
    public function handle(
        Account $account,
        TransactionType $type,
        Money $amount,
        ?Category $category = null,
        ?string $description = null,
        ?CarbonInterface $occurredOn = null,
    ): Transaction {
        if ($type === TransactionType::Transfer) {
            throw new InvalidArgumentException('Use TransferBetweenAccounts for transfers.');
        }

        if ($amount->currency !== $account->currency) {
            throw new InvalidArgumentException('Transaction currency must match the account currency.');
        }

        return $account->transactions()->create([
            'type' => $type,
            'amount' => $amount->absolute(),
            'currency' => $account->currency,
            'is_inflow' => $type->defaultIsInflow(),
            'category_id' => $category?->id,
            'description' => $description,
            'occurred_on' => $occurredOn ?? now(),
        ]);
    }
}
