<?php

namespace App\Application\Goals;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Takes money out of a savings goal, never more than what was saved. When
 * the goal is linked to an account the withdrawal flows back as an income
 * movement tagged with savings_goal_id.
 */
class WithdrawFromGoal
{
    public function handle(SavingsGoal $goal, Money $amount): SavingsGoal
    {
        if ($amount->currency !== $goal->currency) {
            throw new InvalidArgumentException('Withdrawal currency must match the goal currency.');
        }

        $magnitude = $amount->absolute();

        if ($magnitude->minorUnits > $goal->current_amount->minorUnits) {
            throw new InvalidArgumentException('Withdrawal exceeds the amount saved in the goal.');
        }

        $account = $goal->account;

        if ($account === null) {
            $goal->adjustCurrent(-$magnitude->minorUnits);

            return $goal;
        }

        return DB::transaction(function () use ($goal, $account, $magnitude) {
            $account->transactions()->create([
                'type' => TransactionType::Income,
                'amount' => $magnitude,
                'currency' => $account->currency,
                'is_inflow' => true,
                'description' => "Retiro de meta: {$goal->name}",
                'occurred_on' => now(),
                'savings_goal_id' => $goal->id,
            ]);

            $goal->adjustCurrent(-$magnitude->minorUnits);

            return $goal;
        });
    }
}
