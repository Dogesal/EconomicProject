<?php

namespace Tests\Feature;

use App\Application\Budgets\CalculateBudgetConsumption;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_consumption_against_spending(): void
    {
        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();
        Budget::factory()->for($category)->forPeriod(2026, 6)->amount(20000)->create();

        $record = app(RecordTransaction::class);
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $category, null, now()->setDate(2026, 6, 10));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(3000, 'ARS'), $category, null, now()->setDate(2026, 6, 12));

        $result = app(CalculateBudgetConsumption::class)->handle(2026, 6)->first();

        $this->assertSame(800000, $result->spent->minorUnits);
        $this->assertSame(1200000, $result->remaining->minorUnits);
        $this->assertSame(40.0, $result->percentage);
        $this->assertFalse($result->isOverBudget);
    }

    public function test_it_flags_over_budget(): void
    {
        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();
        Budget::factory()->for($category)->forPeriod(2026, 6)->amount(5000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(8000, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $result = app(CalculateBudgetConsumption::class)->handle(2026, 6)->first();

        $this->assertTrue($result->isOverBudget);
    }

    public function test_it_stores_a_budget_via_http(): void
    {
        $category = Category::factory()->expense()->create();
        Account::factory()->currency('ARS')->create();

        $response = $this->post(route('budgets.store'), [
            'category_id' => $category->id,
            'amount' => 25000,
            'period_year' => 2026,
            'period_month' => 6,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('budgets', ['category_id' => $category->id, 'period_month' => 6]);
    }
}
