<?php

namespace Tests\Feature;

use App\Application\Reports\ExpensesForCategory;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDrilldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_category_expenses_of_the_month_and_currency(): void
    {
        $food = Category::factory()->expense()->create(['name' => 'Comida']);
        $transport = Category::factory()->expense()->create(['name' => 'Transporte']);
        $account = Account::factory()->withInitialBalance(500000)->create();
        $usdAccount = Account::factory()->withInitialBalance(500000)->currency('USD')->create();

        $record = app(RecordTransaction::class);
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $food, 'Mercado', now()->setDate(2026, 6, 10));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(3000, 'ARS'), $food, 'Delivery', now()->setDate(2026, 6, 20));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1000, 'ARS'), $transport, null, now()->setDate(2026, 6, 12));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(2000, 'ARS'), $food, null, now()->setDate(2026, 5, 10));
        $record->handle($usdAccount, TransactionType::Expense, Money::fromDecimal(100, 'USD'), $food, null, now()->setDate(2026, 6, 15));

        $result = app(ExpensesForCategory::class)->handle($food->id, 2026, 6, 'ARS');

        $this->assertSame(2, $result->count);
        $this->assertSame(800000, $result->total->minorUnits);
        $this->assertSame('Comida', $result->category->name);
        $this->assertSame('2026-06-20', $result->transactions->first()->occurredOn);
        $this->assertSame('Comida', $result->transactions->first()->category->name);
    }

    public function test_it_returns_an_empty_result_for_a_category_without_expenses(): void
    {
        $category = Category::factory()->expense()->create();
        Account::factory()->currency('ARS')->create();

        $result = app(ExpensesForCategory::class)->handle($category->id, 2026, 6, 'ARS');

        $this->assertSame(0, $result->count);
        $this->assertSame(0, $result->total->minorUnits);
        $this->assertCount(0, $result->transactions);
    }

    public function test_it_returns_null_for_an_unknown_category(): void
    {
        Account::factory()->currency('ARS')->create();

        $this->assertNull(app(ExpensesForCategory::class)->handle('missing-id', 2026, 6, 'ARS'));
    }

    public function test_budgets_screen_omits_the_optional_prop_on_a_normal_visit(): void
    {
        Account::factory()->currency('ARS')->create();

        $this->get(route('budgets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Budgets/Index')->missing('categoryExpenses'));
    }

    public function test_budgets_screen_returns_the_expenses_on_a_partial_reload(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        $account = Account::factory()->withInitialBalance(100000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $version = $this->get(route('budgets.index'))->viewData('page')['version'];

        $this->get(route('budgets.index', ['drill_category' => $category->id, 'year' => 2026, 'month' => 6]), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'Budgets/Index',
            'X-Inertia-Partial-Data' => 'categoryExpenses',
        ])
            ->assertOk()
            ->assertJsonPath('props.categoryExpenses.count', 1)
            ->assertJsonPath('props.categoryExpenses.total.minorUnits', 500000)
            ->assertJsonPath('props.categoryExpenses.category.name', 'Comida');
    }

    public function test_reports_screen_returns_the_expenses_on_a_partial_reload(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Comida']);
        $account = Account::factory()->withInitialBalance(100000)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(5000, 'ARS'), $category, null, now()->setDate(2026, 6, 10)
        );

        $version = $this->get(route('reports.index'))->viewData('page')['version'];

        $this->get(route('reports.index', ['drill_category' => $category->id, 'year' => 2026, 'month' => 6]), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $version,
            'X-Inertia-Partial-Component' => 'Reports/Index',
            'X-Inertia-Partial-Data' => 'categoryExpenses',
        ])
            ->assertOk()
            ->assertJsonPath('props.categoryExpenses.count', 1)
            ->assertJsonPath('props.categoryExpenses.total.minorUnits', 500000);
    }
}
