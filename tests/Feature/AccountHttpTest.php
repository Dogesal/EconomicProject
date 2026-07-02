<?php

namespace Tests\Feature;

use App\Domain\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_accounts_screen(): void
    {
        Account::factory()->count(2)->create();

        $this->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounts/Index')
                ->has('accounts', 2)
            );
    }

    public function test_it_creates_an_account_with_initial_balance(): void
    {
        $response = $this->post(route('accounts.store'), [
            'name' => 'Banco Nación',
            'type' => 'bank',
            'currency' => 'ARS',
            'initial_balance' => 1500.75,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accounts', ['name' => 'Banco Nación', 'currency' => 'ARS']);

        $account = Account::first();
        $this->assertSame(150075, $account->current_balance->minorUnits);
        $this->assertSame(150075, $account->initial_balance->minorUnits);
    }

    public function test_the_dashboard_totals_group_by_currency(): void
    {
        Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        Account::factory()->currency('USD')->withInitialBalance(50)->create();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('totals', 2)
                ->has('accounts', 2)
            );
    }
}
