<?php

namespace Tests\Feature;

use App\Application\Recurring\GenerateDueRecurringTransactions;
use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Models\Account;
use App\Domain\Models\RecurringTransaction;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_recurring_transaction_can_be_created_from_the_http_endpoint(): void
    {
        $account = Account::factory()->create();

        $this->post(route('recurring.store'), [
            'account_id' => $account->id,
            'category_id' => null,
            'type' => 'expense',
            'amount' => 1200,
            'description' => 'Alquiler',
            'frequency' => 'monthly',
            'interval' => 1,
            'next_run_on' => now()->addMonth()->startOfMonth()->toDateString(),
            'end_on' => null,
        ])->assertRedirect()->assertSessionHas('success');

        $recurring = RecurringTransaction::sole();

        $this->assertSame(RecurrenceFrequency::Monthly, $recurring->frequency);
        $this->assertSame('Alquiler', $recurring->description);
        $this->assertSame(120000, $recurring->amount->minorUnits);
    }

    public function test_it_catches_up_all_missed_monthly_occurrences(): void
    {
        $account = Account::factory()->withInitialBalance(0)->create();

        RecurringTransaction::factory()
            ->for($account)
            ->expense()
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun('2026-03-01')
            ->create(['amount' => Money::fromDecimal(1000, 'ARS'), 'currency' => 'ARS']);

        // As of June, three occurrences (Mar, Apr, May, Jun) should exist.
        $generated = app(GenerateDueRecurringTransactions::class)->handle(Carbon::parse('2026-06-15'));

        $this->assertSame(4, $generated);
        $this->assertSame(4, Transaction::count());
        $this->assertSame('2026-07-01', RecurringTransaction::first()->next_run_on->toDateString());
    }

    public function test_it_stops_at_the_end_date(): void
    {
        $account = Account::factory()->withInitialBalance(0)->create();

        RecurringTransaction::factory()
            ->for($account)
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun('2026-01-01')
            ->create([
                'end_on' => '2026-03-31',
                'amount' => Money::fromDecimal(500, 'ARS'),
                'currency' => 'ARS',
            ]);

        $generated = app(GenerateDueRecurringTransactions::class)->handle(Carbon::parse('2026-12-31'));

        $this->assertSame(3, $generated); // Jan, Feb, Mar only
    }

    public function test_it_does_not_generate_before_the_next_run_date(): void
    {
        $account = Account::factory()->withInitialBalance(0)->create();
        RecurringTransaction::factory()->for($account)->nextRun('2030-01-01')->create();

        $generated = app(GenerateDueRecurringTransactions::class)->handle(Carbon::parse('2026-06-15'));

        $this->assertSame(0, $generated);
        $this->assertSame(0, Transaction::count());
    }
}
