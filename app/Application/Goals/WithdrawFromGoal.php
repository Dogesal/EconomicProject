<?php

namespace App\Application\Goals;

use App\Domain\Enums\GoalStatus;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use InvalidArgumentException;

class WithdrawFromGoal
{
    public function handle(SavingsGoal $goal, Money $amount): SavingsGoal
    {
        if ($amount->currency !== $goal->currency) {
            throw new InvalidArgumentException('Withdrawal currency must match the goal currency.');
        }

        $remaining = max(0, $goal->current_amount->minorUnits - $amount->absolute()->minorUnits);
        $goal->current_amount = Money::fromMinor($remaining, $goal->currency);

        if (! $goal->isReached() && $goal->status === GoalStatus::Completed) {
            $goal->status = GoalStatus::Active;
        }

        $goal->save();

        return $goal;
    }
}
