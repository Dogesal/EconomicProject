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
use App\Domain\Models\Setting;
use App\Domain\ValueObjects\Money;
use App\Support\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
                // 100.000 inicial - 5.000 gasto = 95.000 disponibles; deuda 5.000.
                ->where('netBalance.available.minorUnits', 9500000)
                ->where('netBalance.debts.minorUnits', 500000)
                ->where('netBalance.net.minorUnits', 9000000)
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
                ->where('netBalance', null)
            );
    }

    public function test_opening_the_dashboard_applies_pending_whatsapp_messages(): void
    {
        config(['services.whatsapp_sync.url' => 'https://sync.test']);

        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        Setting::put(WhatsAppLink::API_TOKEN_KEY, 'token');
        Setting::put(WhatsAppLink::LINKED_KEY, '1');

        Http::fake([
            'https://sync.test/api/messages/pending' => Http::response(['data' => [[
                'id' => (string) Str::uuid(),
                'type' => 'expense',
                'amount' => '100.00',
                'category_text' => 'comida',
                'account_text' => null,
                'account_id' => $account->id,
                'description' => null,
                'occurred_on' => today()->toDateString(),
                'raw_text' => 'comida 100 hoy',
                'received_at' => now()->toIso8601String(),
            ]]]),
            'https://sync.test/api/messages/ack' => Http::response(['acked' => 1]),
            'https://sync.test/api/devices/me/accounts' => Http::response(null, 204),
        ]);

        $this->get(route('dashboard'))->assertOk();

        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(90000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_dashboard_makes_no_network_calls_when_whatsapp_is_not_linked(): void
    {
        config(['services.whatsapp_sync.url' => 'https://sync.test']);
        Http::fake();

        $this->get(route('dashboard'))->assertOk();

        Http::assertNothingSent();
    }
}
