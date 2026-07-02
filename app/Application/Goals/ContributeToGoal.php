<?php

namespace App\Application\Goals;

use App\Domain\Enums\GoalStatus;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use InvalidArgumentException;

class ContributeToGoal
{
    public function handle(SavingsGoal $goal, Money $amount): SavingsGoal
    {
        if ($amount->currency !== $goal->currency) {
            throw new InvalidArgumentException('Contribution currency must match the goal currency.');
        }

        $goal->current_amount = $goal->current_amount->plus($amount->absolute());

        if ($goal->isReached()) {
            $goal->status = GoalStatus::Completed;
        }

        $goal->save();

        return $goal;
    }
}
