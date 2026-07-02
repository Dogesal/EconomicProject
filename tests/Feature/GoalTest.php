<?php

namespace Tests\Feature;

use App\Application\Goals\ContributeToGoal;
use App\Application\Goals\WithdrawFromGoal;
use App\Domain\Enums\GoalStatus;
use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
