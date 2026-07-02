<?php

namespace Database\Factories;

use App\Domain\Enums\AccountType;
use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = 'ARS';
        $initial = Money::fromDecimal(fake()->numberBetween(0, 500000), $currency);

        return [
            'name' => fake()->randomElement(['Efectivo', 'Banco Galicia', 'Mercado Pago', 'Ahorros']).' '.fake()->randomLetter(),
            'type' => fake()->randomElement(AccountType::cases()),
            'currency' => $currency,
            'initial_balance' => $initial,
            'current_balance' => $initial,
            'color' => fake()->hexColor(),
            'is_archived' => false,
        ];
    }

    public function currency(string $currency): static
    {
        return $this->state(function () use ($currency) {
            $initial = Money::fromDecimal(fake()->numberBetween(0, 500000), $currency);

            return [
                'currency' => $currency,
                'initial_balance' => $initial,
                'current_balance' => $initial,
            ];
        });
    }

    public function withInitialBalance(int|float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'initial_balance' => Money::fromDecimal($amount, $attributes['currency']),
            'current_balance' => Money::fromDecimal($amount, $attributes['currency']),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }
}
