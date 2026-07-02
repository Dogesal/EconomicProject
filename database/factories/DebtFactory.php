<?php

namespace Database\Factories;

use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\DebtStatus;
use App\Domain\Models\Debt;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    protected $model = Debt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = 'ARS';

        return [
            'name' => fake()->randomElement(['Tarjeta Visa', 'Préstamo banco', 'Fiado almacén', 'Préstamo a Juan']),
            'direction' => fake()->randomElement(DebtDirection::cases()),
            'original_amount' => Money::fromDecimal(fake()->numberBetween(1000, 100000), $currency),
            'paid_amount' => Money::zero($currency),
            'currency' => $currency,
            'due_date' => fake()->optional()->dateTimeBetween('now', '+6 months')?->format('Y-m-d'),
            'status' => DebtStatus::Active,
        ];
    }

    public function iOwe(): static
    {
        return $this->state(fn () => ['direction' => DebtDirection::IOwe]);
    }

    public function owedToMe(): static
    {
        return $this->state(fn () => ['direction' => DebtDirection::OwedToMe]);
    }

    public function amount(int|float $amount, string $currency = 'ARS'): static
    {
        return $this->state(fn () => [
            'original_amount' => Money::fromDecimal($amount, $currency),
            'paid_amount' => Money::zero($currency),
            'currency' => $currency,
        ]);
    }

    public function settled(): static
    {
        return $this->state(fn (array $attributes) => [
            'paid_amount' => $attributes['original_amount'],
            'status' => DebtStatus::Settled,
        ]);
    }
}
