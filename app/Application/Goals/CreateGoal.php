<?php

namespace App\Application\Goals;

use App\Domain\Enums\GoalStatus;
use App\Domain\Models\Account;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;

class CreateGoal
{
    public function handle(
        string $name,
        Money $target,
        ?CarbonInterface $targetDate = null,
        ?Account $account = null,
    ): SavingsGoal {
        return SavingsGoal::create([
            'name' => $name,
            'target_amount' => $target,
            'current_amount' => Money::zero($target->currency),
            'currency' => $target->currency,
            'target_date' => $targetDate,
            'account_id' => $account?->id,
            'status' => GoalStatus::Active,
        ]);
    }
}
