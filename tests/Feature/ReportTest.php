<?php

namespace Tests\Feature;

use App\Application\Reports\MonthlyEvolution;
use App\Application\Reports\SpendingByCategory;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_spending_by_category_groups_and_ranks_expenses(): void
    {
        $account = Account::factory()->withInitialBalance(500000)->create();
        $food = Category::factory()->expense()->create(['name' => 'Comida']);
        $transport = Category::factory()->expense()->create(['name' => 'Transporte']);

        $record = app(RecordTransaction::class);
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(9000, 'ARS'), $food, null, now()->setDate(2026, 6, 5));
        $record->handle($account, TransactionType::Expense, Money::fromDecimal(1000, 'ARS'), $transport, null, now()->setDate(2026, 6, 6));

        $result = app(SpendingByCategory::class)->handle(2026, 6, 'ARS');

        $this->assertCount(2, $result);
        $this->assertSame('Comida', $result->first()->categoryName);
        $this->assertSame(90.0, $result->first()->percentage);
    }

    public function test_monthly_evolution_returns_the_requested_window(): void
    {
        $account = Account::factory()->withInitialBalance(0)->create();

        app(RecordTransaction::class)->handle(
            $account, TransactionType::Income, Money::fromDecimal(100000, 'ARS'), null, null, now()
        );

        $result = app(MonthlyEvolution::class)->handle('ARS', 6);

        $this->assertCount(6, $result);
        $this->assertSame(10000000, $result->last()->income->minorUnits);
    }

    public function test_reports_screen_renders(): void
    {
        Account::factory()->currency('ARS')->create();

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Reports/Index')->has('monthlyEvolution', 6));
    }
}
