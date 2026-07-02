<?php

namespace Tests\Feature;

use App\Application\Goals\ContributeToGoal;
use App\Application\Goals\WithdrawFromGoal;
use App\Application\Transactions\DeleteTransaction;
use App\Domain\Enums\GoalStatus;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_contributions_accumulate_and_complete_the_goal(): void
    {
        $goal = SavingsGoal::factory()->target(1000)->create();
        $contribute = app(ContributeToGoal::class);

        $contribute->handle($goal, Money::fromDecimal(400, 'ARS'));
        $this->assertSame(GoalStatus::Active, $goal->fresh()->status);

        $contribute->handle($goal, Money::fromDecimal(600, 'ARS'));
        $goal->refresh();

        $this->assertSame(100000, $goal->current_amount->minorUnits);
        $this->assertSame(GoalStatus::Completed, $goal->status);
        $this->assertSame(100.0, $goal->progressPercentage());
    }

    public function test_withdrawing_reactivates_a_completed_goal(): void
    {
        $goal = SavingsGoal::factory()->target(1000)->create();
        app(ContributeToGoal::class)->handle($goal, Money::fromDecimal(1000, 'ARS'));

        app(WithdrawFromGoal::class)->handle($goal, Money::fromDecimal(300, 'ARS'));
        $goal->refresh();

        $this->assertSame(70000, $goal->current_amount->minorUnits);
        $this->assertSame(GoalStatus::Active, $goal->status);
    }

    public function test_it_creates_a_goal_via_http(): void
    {
        $response = $this->post(route('goals.store'), [
            'name' => 'Vacaciones',
            'target_amount' => 300000,
            'currency' => 'ARS',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('savings_goals', ['name' => 'Vacaciones']);
    }

    public function test_a_goal_linked_to_an_account_inherits_its_currency(): void
    {
        $account = Account::factory()->currency('USD')->create();

        $this->post(route('goals.store'), [
            'name' => 'Viaje',
            'target_amount' => 1000,
            'currency' => 'ARS', // Ignorada: manda la moneda de la cuenta.
            'account_id' => $account->id,
        ])->assertRedirect();

        $goal = SavingsGoal::sole();

        $this->assertSame('USD', $goal->currency);
        $this->assertSame($account->id, $goal->account_id);
    }

    public function test_contributing_to_a_linked_goal_moves_real_money(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $goal = SavingsGoal::factory()->target(500)->create(['account_id' => $account->id]);

        $this->post(route('goals.contribute', $goal), ['amount' => 200])->assertRedirect();

        $this->assertSame(20000, $goal->refresh()->current_amount->minorUnits);
        $this->assertSame(80000, $account->refresh()->current_balance->minorUnits);

        $movement = $goal->movements()->sole();
        $this->assertSame(TransactionType::Expense, $movement->type);
        $this->assertSame('Aporte a meta: '.$goal->name, $movement->description);
    }

    public function test_a_contribution_larger_than_the_account_balance_is_rejected(): void
    {
        $account = Account::factory()->withInitialBalance(100)->create();
        $goal = SavingsGoal::factory()->target(500)->create(['account_id' => $account->id]);

        $this->post(route('goals.contribute', $goal), ['amount' => 150])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, $goal->refresh()->current_amount->minorUnits);
        $this->assertSame(10000, $account->refresh()->current_balance->minorUnits);
    }

    public function test_withdrawing_from_a_linked_goal_returns_money_to_the_account(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $goal = SavingsGoal::factory()->target(500)->create(['account_id' => $account->id]);
        app(ContributeToGoal::class)->handle($goal, Money::fromDecimal(300, 'ARS'));

        $this->post(route('goals.withdraw', $goal), ['amount' => 100])->assertRedirect();

        $this->assertSame(20000, $goal->refresh()->current_amount->minorUnits);
        // 1000 - 300 aporte + 100 retiro = 800.
        $this->assertSame(80000, $account->refresh()->current_balance->minorUnits);
        $this->assertTrue($goal->movements()->where('is_inflow', true)->exists());
    }

    public function test_withdrawing_more_than_saved_is_rejected(): void
    {
        $goal = SavingsGoal::factory()->target(1000)->create();
        app(ContributeToGoal::class)->handle($goal, Money::fromDecimal(100, 'ARS'));

        $this->post(route('goals.withdraw', $goal), ['amount' => 250])
            ->assertSessionHasErrors('amount');

        $this->assertSame(10000, $goal->refresh()->current_amount->minorUnits);

        $this->expectException(InvalidArgumentException::class);
        app(WithdrawFromGoal::class)->handle($goal, Money::fromDecimal(250, 'ARS'));
    }

    public function test_deleting_a_contribution_movement_reverts_the_goal(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $goal = SavingsGoal::factory()->target(500)->create(['account_id' => $account->id]);
        app(ContributeToGoal::class)->handle($goal, Money::fromDecimal(500, 'ARS'));
        $this->assertSame(GoalStatus::Completed, $goal->refresh()->status);

        app(DeleteTransaction::class)->handle($goal->movements()->sole());

        $goal->refresh();
        $this->assertSame(0, $goal->current_amount->minorUnits);
        $this->assertSame(GoalStatus::Active, $goal->status);
        $this->assertSame(100000, $account->refresh()->current_balance->minorUnits);
    }

    public function test_an_unlinked_goal_still_works_as_a_plain_counter(): void
    {
        $goal = SavingsGoal::factory()->target(1000)->create(['account_id' => null]);

        $this->post(route('goals.contribute', $goal), ['amount' => 400])->assertRedirect();

        $this->assertSame(40000, $goal->refresh()->current_amount->minorUnits);
        $this->assertSame(0, $goal->movements()->count());
    }
}
