<?php

namespace Database\Factories;

use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\RecurringTransaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    protected $model = RecurringTransaction::class;

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
            'amount' => Money::fromDecimal(fake()->numberBetween(1000, 50000), $currency),
            'currency' => $currency,
            'description' => fake()->randomElement(['Alquiler', 'Netflix', 'Gimnasio', 'Sueldo']),
            'frequency' => RecurrenceFrequency::Monthly,
            'interval' => 1,
            'next_run_on' => now()->toDateString(),
            'end_on' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Income]);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Expense]);
    }

    public function frequency(RecurrenceFrequency $frequency, int $interval = 1): static
    {
        return $this->state(fn () => ['frequency' => $frequency, 'interval' => $interval]);
    }

    public function nextRun(string $date): static
    {
        return $this->state(fn () => ['next_run_on' => $date]);
    }
}
