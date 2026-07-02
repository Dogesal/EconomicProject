<?php

namespace App\Application\Goals;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Adds money to a savings goal. When the goal is linked to an account the
 * contribution is a real movement: an expense leaves the account (capped by
 * its balance) and carries savings_goal_id so deleting it reverts the goal
 * (see TransactionObserver). Unlinked goals remain a simple counter.
 */
class ContributeToGoal
{
    public function handle(SavingsGoal $goal, Money $amount): SavingsGoal
    {
        if ($amount->currency !== $goal->currency) {
            throw new InvalidArgumentException('Contribution currency must match the goal currency.');
        }

        $magnitude = $amount->absolute();
        $account = $goal->account;

        if ($account === null) {
            $goal->adjustCurrent($magnitude->minorUnits);

            return $goal;
        }

        if ($magnitude->minorUnits > $account->current_balance->minorUnits) {
            throw new InvalidArgumentException('Insufficient funds in the account.');
        }

        return DB::transaction(function () use ($goal, $account, $magnitude) {
            $account->transactions()->create([
                'type' => TransactionType::Expense,
                'amount' => $magnitude,
                'currency' => $account->currency,
                'is_inflow' => false,
                'description' => "Aporte a meta: {$goal->name}",
                'occurred_on' => now(),
                'savings_goal_id' => $goal->id,
            ]);

            $goal->adjustCurrent($magnitude->minorUnits);

            return $goal;
        });
    }
}
