<?php

namespace Tests\Feature;

use App\Application\Recurring\GenerateDueRecurringTransactions;
use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Models\Account;
use App\Domain\Models\RecurringTransaction;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_an_expense_larger_than_the_balance_is_rejected(): void
    {
        $account = Account::factory()->withInitialBalance(100)->create();

        $this->post(route('transactions.store'), [
            'account_id' => $account->id,
            'type' => 'expense',
            'amount' => 150,
            'occurred_on' => '2026-06-15',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_an_income_is_not_capped_by_the_balance(): void
    {
        $account = Account::factory()->withInitialBalance(0)->create();

        $this->post(route('transactions.store'), [
            'account_id' => $account->id,
            'type' => 'income',
            'amount' => 5000,
            'occurred_on' => '2026-06-15',
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_editing_an_expense_frees_its_own_amount_first(): void
    {
        $account = Account::factory()->withInitialBalance(100)->create();
        $transaction = Transaction::factory()->expense()->amount(80)->for($account)->create();
        $account->recalculateBalance();

        // Balance is 20; raising the expense to 100 is fine (frees its 80),
        // but 120 exceeds initial funds and must be rejected.
        $this->put(route('transactions.update', $transaction), [
            'amount' => 100,
            'occurred_on' => '2026-06-15',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->put(route('transactions.update', $transaction), [
            'amount' => 120,
            'occurred_on' => '2026-06-15',
        ])->assertSessionHasErrors('amount');
    }

    public function test_a_transfer_larger_than_the_source_balance_is_rejected(): void
    {
        $from = Account::factory()->withInitialBalance(100)->create();
        $to = Account::factory()->withInitialBalance(0)->create();

        $this->post(route('transfers.store'), [
            'from_account_id' => $from->id,
            'to_account_id' => $to->id,
            'amount' => 200,
            'occurred_on' => '2026-06-15',
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_recurring_transactions_are_generated_even_without_balance(): void
    {
        // Automated catch-up must never be blocked by the balance cap.
        $account = Account::factory()->withInitialBalance(0)->create();

        RecurringTransaction::factory()
            ->for($account)
            ->expense()
            ->frequency(RecurrenceFrequency::Monthly)
            ->nextRun('2026-06-01')
            ->create(['amount' => Money::fromDecimal(500, 'ARS'), 'currency' => 'ARS']);

        $generated = app(GenerateDueRecurringTransactions::class)->handle(Carbon::parse('2026-06-15'));

        $this->assertSame(1, $generated);
        $this->assertSame(-50000, $account->fresh()->current_balance->minorUnits);
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
