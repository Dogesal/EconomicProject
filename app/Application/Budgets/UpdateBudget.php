<?php

namespace App\Application\Budgets;

use App\Domain\Models\Budget;
use App\Domain\ValueObjects\Money;
use InvalidArgumentException;

class UpdateBudget
{
    public function handle(Budget $budget, Money $amount): Budget
    {
        if ($amount->currency !== $budget->currency) {
            throw new InvalidArgumentException('Budget currency cannot change.');
        }

        $budget->update(['amount' => $amount]);

        return $budget;
    }
}
