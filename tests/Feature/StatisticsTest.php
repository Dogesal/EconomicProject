<?php

namespace Tests\Feature;

use App\Application\Statistics\GenerateRecommendations;
use App\Application\Statistics\MonthOverview;
use App\Application\Statistics\SpendingHabits;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_screen_renders(): void
    {
        Account::factory()->currency('ARS')->create();

        $this->get(route('statistics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Statistics/Index')
                ->has('overview')
                ->has('habits')
                ->has('recommendations')
                ->has('trend', 6));
    }

    public function test_month_overview_projects_spending_for_the_current_month(): void
    {
        $this->travelTo(Carbon::create(2026, 6, 15));

        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();
        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(3000, 'ARS'), $category, null, now()->setDate(2026, 6, 5)
        );

        $result = app(MonthOverview::class)->handle(2026, 6, 'ARS');

        $this->assertTrue($result->isCurrentMonth);
        $this->assertSame(15, $result->daysElapsed);
        $this->assertSame(30, $result->daysInMonth);
        $this->assertSame(300000, $result->totalExpense->minorUnits);
        $this->assertSame(20000, $result->averageDailyExpense->minorUnits);
        $this->assertSame(600000, $result->projectedExpense->minorUnits);
    }

    public function test_month_overview_has_no_projection_for_a_past_month(): void
    {
        $this->travelTo(Carbon::create(2026, 7, 10));

        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();
        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(3000, 'ARS'), $category, null, now()->setDate(2026, 6, 5)
        );

        $result = app(MonthOverview::class)->handle(2026, 6, 'ARS');

        $this->assertFalse($result->isCurrentMonth);
        $this->assertNull($result->projectedExpense);
        $this->assertSame(30, $result->daysElapsed);
        $this->assertSame(10000, $result->averageDailyExpense->minorUnits);
    }

    public function test_month_overview_compares_against_the_previous_month(): void
    {
        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(500000)->create();

        $record = app(RecordTransaction::class);
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1000, 'ARS'), $category, null, now()->setDate(2026, 5, 10));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1300, 'ARS'), $category, null, now()->setDate(2026, 6, 10));

        $result = app(MonthOverview::class)->handle(2026, 6, 'ARS');

        $this->assertSame(30.0, $result->changePercentage);
        $this->assertSame(100000, $result->previousMonthExpense->minorUnits);
    }

    public function test_month_overview_change_is_null_when_the_previous_month_is_empty(): void
    {
        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();
        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(1300, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $this->assertNull(app(MonthOverview::class)->handle(2026, 6, 'ARS')->changePercentage);
    }

    public function test_habits_return_top_expenses_dominant_category_and_weekday(): void
    {
        $food = Category::factory()->expense()->create(['name' => 'Comida']);
        $transport = Category::factory()->expense()->create(['name' => 'Transporte']);
        $account = Account::factory()->withInitialBalance(1000000)->create();

        $record = app(RecordTransaction::class);
        // 2026-06-01 and 2026-06-08 are Mondays.
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(6000, 'ARS'), $food, 'Mercado', now()->setDate(2026, 6, 1));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $food, null, now()->setDate(2026, 6, 8));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(4000, 'ARS'), $transport, null, now()->setDate(2026, 6, 9));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(3000, 'ARS'), $food, null, now()->setDate(2026, 6, 10));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(2000, 'ARS'), $food, null, now()->setDate(2026, 6, 11));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1000, 'ARS'), $food, null, now()->setDate(2026, 6, 12));

        $result = app(SpendingHabits::class)->handle(2026, 6, 'ARS');

        $this->assertCount(5, $result->topExpenses);
        $this->assertSame(600000, $result->topExpenses->first()->amount->minorUnits);
        $this->assertSame('Comida', $result->dominantCategory->categoryName);
        $this->assertSame('Lunes', $result->topWeekday->label);
        $this->assertSame(1100000, $result->topWeekday->total->minorUnits);
    }

    public function test_habits_are_empty_without_expenses(): void
    {
        Account::factory()->currency('ARS')->create();

        $result = app(SpendingHabits::class)->handle(2026, 6, 'ARS');

        $this->assertCount(0, $result->topExpenses);
        $this->assertNull($result->dominantCategory);
        $this->assertNull($result->topWeekday);
    }

    public function test_recommendations_flag_over_budget_first(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        $account = Account::factory()->withInitialBalance(100000)->create();
        Budget::factory()->for($category)->forPeriod(2026, 6)->amount(5000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(8000, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $result = app(GenerateRecommendations::class)->handle(2026, 6, 'ARS');

        $this->assertSame('budget_over', $result->first()->type);
        $this->assertSame('danger', $result->first()->severity);
        $this->assertStringContainsString('Comida', $result->first()->title);
    }

    public function test_recommendations_warn_when_near_the_budget_limit(): void
    {
        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();
        Budget::factory()->for($category)->forPeriod(2026, 6)->amount(10000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(9500, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $result = app(GenerateRecommendations::class)->handle(2026, 6, 'ARS');

        $this->assertNotNull($result->firstWhere('type', 'budget_near'));
        $this->assertNull($result->firstWhere('type', 'budget_over'));
    }

    public function test_recommendations_skip_growth_when_the_previous_month_is_empty(): void
    {
        $category = Category::factory()->expense()->create();
        $account = Account::factory()->withInitialBalance(100000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $result = app(GenerateRecommendations::class)->handle(2026, 6, 'ARS');

        $this->assertNull($result->firstWhere('type', 'category_growth'));
    }

    public function test_recommendations_flag_a_growing_category(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        $account = Account::factory()->withInitialBalance(500000)->create();

        $record = app(RecordTransaction::class);
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1000, 'ARS'), $category, null, now()->setDate(2026, 5, 10));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1500, 'ARS'), $category, null, now()->setDate(2026, 6, 10));

        $growth = app(GenerateRecommendations::class)->handle(2026, 6, 'ARS')->firstWhere('type', 'category_growth');

        $this->assertNotNull($growth);
        $this->assertSame('warning', $growth->severity);
        $this->assertStringContainsString('50%', $growth->message);
    }

    public function test_recommendations_suggest_savings_for_a_dominant_category(): void
    {
        $food = Category::factory()->expense()->create(['name' => 'Comida']);
        $transport = Category::factory()->expense()->create(['name' => 'Transporte']);
        $account = Account::factory()->withInitialBalance(500000)->create();

        $record = app(RecordTransaction::class);
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(6000, 'ARS'), $food, null, now()->setDate(2026, 6, 10));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(4000, 'ARS'), $transport, null, now()->setDate(2026, 6, 11));

        $savings = app(GenerateRecommendations::class)->handle(2026, 6, 'ARS')->firstWhere('type', 'savings');

        $this->assertNotNull($savings);
        $this->assertSame('info', $savings->severity);
        $this->assertSame($food->id, $savings->categoryId);
        $this->assertStringContainsString('60%', $savings->message);
    }

    public function test_recommendations_are_empty_for_a_month_without_data(): void
    {
        Account::factory()->currency('ARS')->create();

        $this->assertCount(0, app(GenerateRecommendations::class)->handle(2026, 6, 'ARS'));
    }
}
