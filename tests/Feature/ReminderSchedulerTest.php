<?php

namespace Tests\Feature;

use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Models\Account;
use App\Domain\Models\Debt;
use App\Domain\Models\RecurringTransaction;
use App\Support\ReminderScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private function upcoming(): array
    {
        return app(ReminderScheduler::class)->upcoming();
    }

    public function test_debts_due_within_a_week_are_included(): void
    {
        $soon = Debt::factory()->iOwe()->amount(500)->create(['due_date' => now()->addDays(3)]);
        Debt::factory()->iOwe()->amount(500)->create(['due_date' => now()->addDays(30)]);
        Debt::factory()->iOwe()->amount(500)->settled()->create(['due_date' => now()->addDay()]);
        Debt::factory()->iOwe()->amount(500)->create(['due_date' => null]);

        $reminders = $this->upcoming();

        $this->assertCount(1, $reminders);
        $this->assertSame('debt-'.$soon->id, $reminders[0]['id']);
        $this->assertSame('Deuda por vencer', $reminders[0]['title']);
        $this->assertStringContainsString($soon->name, $reminders[0]['body']);
        $this->assertGreaterThan(now()->getTimestamp(), $reminders[0]['at']);
    }

    public function test_overdue_debts_fire_shortly_with_a_distinct_title(): void
    {
        Debt::factory()->iOwe()->amount(500)->create(['due_date' => now()->subDays(2)]);

        $reminders = $this->upcoming();

        $this->assertCount(1, $reminders);
        $this->assertSame('Deuda vencida', $reminders[0]['title']);
        $this->assertStringContainsString('venció', $reminders[0]['body']);
        // Already past 9am of the due date: fires ~1 minute from now.
        $this->assertLessThanOrEqual(now()->addMinutes(2)->getTimestamp(), $reminders[0]['at']);
    }

    public function test_recurring_transactions_about_to_run_are_included(): void
    {
        $account = Account::factory()->create();

        $due = RecurringTransaction::factory()->for($account)->expense()
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun(now()->addDay()->toDateString())
            ->create(['description' => 'Alquiler']);

        RecurringTransaction::factory()->for($account)->expense()
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun(now()->addDays(10)->toDateString())
            ->create();

        $reminders = $this->upcoming();

        $this->assertCount(1, $reminders);
        $this->assertSame('recurring-'.$due->id, $reminders[0]['id']);
        $this->assertStringContainsString('Alquiler', $reminders[0]['body']);
    }

    public function test_an_ended_recurring_is_not_reminded(): void
    {
        $account = Account::factory()->create();

        RecurringTransaction::factory()->for($account)->expense()
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun(now()->addDay()->toDateString())
            ->create(['end_on' => now()->subDay()]);

        $this->assertCount(0, $this->upcoming());
    }

    public function test_schedule_is_a_noop_outside_the_device_runtime(): void
    {
        Debt::factory()->iOwe()->amount(500)->create(['due_date' => now()->addDay()]);

        // No nativephp_call function in tests: must not throw.
        app(ReminderScheduler::class)->schedule();

        $this->assertTrue(true);
    }
}
