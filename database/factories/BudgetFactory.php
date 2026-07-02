<?php

namespace Database\Factories;

use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = 'ARS';

        return [
            'category_id' => Category::factory()->expense(),
            'period_year' => (int) now()->year,
            'period_month' => (int) now()->month,
            'amount' => Money::fromDecimal(fake()->numberBetween(10000, 200000), $currency),
            'currency' => $currency,
        ];
    }

    public function forPeriod(int $year, int $month): static
    {
        return $this->state(fn () => ['period_year' => $year, 'period_month' => $month]);
    }

    public function amount(int|float $amount, string $currency = 'ARS'): static
    {
        return $this->state(fn () => [
            'amount' => Money::fromDecimal($amount, $currency),
            'currency' => $currency,
        ]);
    }
}
