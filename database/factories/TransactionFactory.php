<?php

namespace Database\Factories;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currency = 'ARS';

        return [
            'account_id' => Account::factory()->currency($currency),
            'category_id' => null,
            'type' => TransactionType::Expense,
            'amount' => Money::fromDecimal(fake()->numberBetween(100, 50000), $currency),
            'currency' => $currency,
            'is_inflow' => false,
            'description' => fake()->optional()->sentence(3),
            'occurred_on' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'transfer_group_id' => null,
            'recurring_transaction_id' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Income,
            'is_inflow' => true,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn () => [
            'type' => TransactionType::Expense,
            'is_inflow' => false,
        ]);
    }

    public function amount(int|float $amount, string $currency = 'ARS'): static
    {
        return $this->state(fn () => [
            'amount' => Money::fromDecimal($amount, $currency),
            'currency' => $currency,
        ]);
    }
}
