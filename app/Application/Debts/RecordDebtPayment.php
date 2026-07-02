<?php

namespace App\Application\Debts;

use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Debt;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Registers a payment against a debt as a real account movement: an expense
 * when the user owes (money leaves the account) or an income when the user
 * is owed (money comes in). The linked transaction carries the debt_id so
 * deleting it reverts the payment (see TransactionObserver).
 */
class RecordDebtPayment
{
    public function handle(
        Debt $debt,
        Account $account,
        Money $amount,
        ?CarbonInterface $occurredOn = null,
    ): Transaction {
        if ($debt->status === DebtStatus::Settled) {
            throw new InvalidArgumentException('The debt is already settled.');
        }

        if ($debt->currency !== $account->currency || $amount->currency !== $debt->currency) {
            throw new InvalidArgumentException('Payment currency must match both the debt and the account.');
        }

        return DB::transaction(function () use ($debt, $account, $amount, $occurredOn) {
            $type = $debt->direction->paymentTransactionType();

            $transaction = $account->transactions()->create([
                'type' => $type,
                'amount' => $amount->absolute(),
                'currency' => $account->currency,
                'is_inflow' => $type === TransactionType::Income,
                'description' => $debt->direction === DebtDirection::IOwe
                    ? "Pago de deuda: {$debt->name}"
                    : "Cobro de deuda: {$debt->name}",
                'occurred_on' => $occurredOn ?? now(),
                'debt_id' => $debt->id,
            ]);

            $debt->applyPayment($amount);

            return $transaction;
        });
    }
}
