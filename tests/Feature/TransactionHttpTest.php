<?php

namespace Tests\Feature;

use App\Domain\Models\Account;
use App\Domain\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_an_expense_and_updates_the_balance(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();

        $response = $this->post(route('transactions.store'), [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 250.50,
            'occurred_on' => '2026-06-15',
            'description' => 'Supermercado',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame(74950, $account->fresh()->current_balance->minorUnits);
    }

    public function test_it_validates_transaction_input(): void
    {
        $response = $this->post(route('transactions.store'), [
            'type' => 'expense',
            'amount' => -5,
        ]);

        $response->assertSessionHasErrors(['account_id', 'amount', 'occurred_on']);
    }

    public function test_it_stores_a_transfer_between_accounts(): void
    {
        $from = Account::factory()->withInitialBalance(1000)->create();
        $to = Account::factory()->withInitialBalance(0)->create();

        $response = $this->post(route('transfers.store'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 400,
            'occurred_on' => '2026-06-15',
        ]);

        $response->assertRedirect();
        $this->assertSame(60000, $from->fresh()->current_balance->minorUnits);
        $this->assertSame(40000, $to->fresh()->current_balance->minorUnits);
    }

    public function test_it_rejects_transfer_to_same_account(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();

        $response = $this->post(route('transfers.store'), [
            'from_account_id' => $account->id,
            'to_account_id' => $account->id,
            'amount' => 400,
            'occurred_on' => '2026-06-15',
        ]);

        $response->assertSessionHasErrors('to_account_id');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_it_deletes_a_transaction(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $transaction = Transaction::factory()->expense()->amount(300)->for($account)->create();

        $account->recalculateBalance();
        $this->assertSame(70000, $account->fresh()->current_balance->minorUnits);

        $response = $this->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect();
        $this->assertSame(100000, $account->fresh()->current_balance->minorUnits);
        $this->assertSoftDeleted($transaction);
    }
}
