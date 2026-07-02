<?php

namespace App\Application\Debts;

use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Models\Debt;
use App\Domain\ValueObjects\Money;
use Carbon\CarbonInterface;

class CreateDebt
{
    public function handle(
        string $name,
        DebtDirection $direction,
        Money $amount,
        ?CarbonInterface $dueDate = null,
    ): Debt {
        return Debt::create([
            'name' => $name,
            'direction' => $direction,
            'original_amount' => $amount,
            'paid_amount' => Money::zero($amount->currency),
            'currency' => $amount->currency,
            'due_date' => $dueDate,
            'status' => DebtStatus::Active,
        ]);
    }
}
