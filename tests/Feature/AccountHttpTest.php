<?php

namespace Tests\Feature;

use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_accounts_screen(): void
    {
        Account::factory()->count(2)->create();
        Account::factory()->archived()->create();

        $this->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounts/Index')
                ->has('accounts', 2)
                ->has('archivedAccounts', 1)
            );
    }

    public function test_deleting_an_account_with_movements_archives_it(): void
    {
        $account = Account::factory()->currency('ARS')->withInitialBalance(1000)->create();
        app(RecordTransaction::class)->handle(
            $account, TransactionType::Expense, Money::fromDecimal(100, 'ARS'), null, null, now()
        );

        $this->delete(route('accounts.destroy', $account))->assertRedirect();

        // Sigue en la base, ahora archivada; nada se borró.
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_archived' => true]);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_deleting_an_empty_account_removes_it(): void
    {
        $account = Account::factory()->create();

        $this->delete(route('accounts.destroy', $account))->assertRedirect();

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_archived_accounts_cannot_be_edited(): void
    {
        $account = Account::factory()->archived()->create(['name' => 'Vieja']);

        $this->put(route('accounts.update', $account), [
            'name' => 'Nueva',
            'type' => $account->type->value,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'Vieja']);
    }

    public function test_an_archived_account_can_be_restored(): void
    {
        $account = Account::factory()->archived()->create();

        $this->post(route('accounts.restore', $account))->assertRedirect();

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'is_archived' => false]);
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
