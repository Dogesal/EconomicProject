<?php

namespace Database\Factories;

use App\Domain\Enums\GoalStatus;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoal>
 */
class SavingsGoalFactory extends Factory
{
    protected $model = SavingsGoal::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = 'ARS';

        return [
            'name' => fake()->randomElement(['Vacaciones', 'Notebook', 'Fondo de emergencia', 'Auto']),
            'target_amount' => Money::fromDecimal(fake()->numberBetween(100000, 1000000), $currency),
            'current_amount' => Money::zero($currency),
            'currency' => $currency,
            'target_date' => fake()->optional()->dateTimeBetween('now', '+1 year')?->format('Y-m-d'),
            'account_id' => null,
            'status' => GoalStatus::Active,
        ];
    }

    public function target(int|float $amount, string $currency = 'ARS'): static
    {
        return $this->state(fn () => [
            'target_amount' => Money::fromDecimal($amount, $currency),
            'current_amount' => Money::zero($currency),
            'currency' => $currency,
        ]);
    }
}
