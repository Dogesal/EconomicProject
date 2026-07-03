<?php

namespace Tests\Feature;

use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\Models\Debt;
use App\Domain\Models\RecurringTransaction;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_surfaces_month_summary_budgets_goals_debts_and_recurring(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(100000)->create();
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        Budget::factory()->for($category)->forPeriod((int) now()->year, (int) now()->month)->amount(20000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $category, null, now()
        );

        SavingsGoal::factory()->target(10000)->create();
        Debt::factory()->iOwe()->amount(5000)->create(['due_date' => now()->subDay()->toDateString()]);
        RecurringTransaction::factory()->for($account)->nextRun(now()->addDay()->toDateString())->create();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->where('monthSummary.expense.minorUnits', 500000)
                ->has('topSpending', 1)
                ->where('topSpending.0.categoryName', 'Comida')
                ->has('budgets', 1)
                ->where('budgets.0.percentage', 25)
                ->has('goals', 1)
                ->has('debtSummary.iOwe', 1)
                ->where('debtSummary.overdueCount', 1)
                ->has('upcomingRecurring', 1)
            );
    }

    public function test_it_renders_with_no_data_at_all(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard')
                ->has('budgets', 0)
                ->has('goals', 0)
                ->has('upcomingRecurring', 0)
                ->where('debtSummary.overdueCount', 0)
            );
    }
}
