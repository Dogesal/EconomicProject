<?php

namespace Tests\Feature;

use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Models\Account;
use App\Domain\Models\RecurringTransaction;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BootTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_catches_up_due_recurring_transactions(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(0)->create();

        RecurringTransaction::factory()
            ->for($account)
            ->expense()
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun(now()->subMonth()->toDateString())
            ->create(['amount' => Money::fromDecimal(500, 'ARS'), 'currency' => 'ARS']);

        $this->assertSame(0, Transaction::count());

        $this->post(route('boot.tasks'))->assertRedirect();

        $this->assertGreaterThanOrEqual(1, Transaction::count());
    }

    public function test_it_is_a_noop_when_nothing_is_due(): void
    {
        $account = Account::factory()->withInitialBalance(0)->create();
        RecurringTransaction::factory()->for($account)->nextRun(now()->addMonth()->toDateString())->create();

        $this->post(route('boot.tasks'))->assertRedirect();

        $this->assertSame(0, Transaction::count());
    }
}
