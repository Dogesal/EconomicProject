<?php

namespace Tests\Feature;

use App\Application\Transactions\DeleteTransaction;
use App\Application\Transactions\RecordTransaction;
use App\Application\Transactions\TransferBetweenAccounts;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recording_income_increases_the_account_balance(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();

        app(RecordTransaction::class)->handle(
            account: $account,
            type: TransactionType::Income,
            amount: Money::fromDecimal(500, 'ARS'),
        );

        $this->assertSame(150000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_recording_expense_decreases_the_account_balance(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();

        app(RecordTransaction::class)->handle(
            account: $account,
            type: TransactionType::Expense,
            amount: Money::fromDecimal(300, 'ARS'),
        );

        $this->assertSame(70000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_transfer_moves_money_between_accounts(): void
    {
        $from = Account::factory()->withInitialBalance(1000)->create();
        $to = Account::factory()->withInitialBalance(200)->create();

        app(TransferBetweenAccounts::class)->handle(
            from: $from,
            to: $to,
            amount: Money::fromDecimal(400, 'ARS'),
        );

        $this->assertSame(60000, $from->fresh()->current_balance->minorUnits);
        $this->assertSame(60000, $to->fresh()->current_balance->minorUnits);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_deleting_a_transaction_reverts_the_balance(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();

        $transaction = app(RecordTransaction::class)->handle(
            account: $account,
            type: TransactionType::Expense,
            amount: Money::fromDecimal(300, 'ARS'),
        );

        $this->assertSame(70000, $account->fresh()->current_balance->minorUnits);

        app(DeleteTransaction::class)->handle($transaction);

        $this->assertSame(100000, $account->fresh()->current_balance->minorUnits);
    }

    public function test_deleting_a_transfer_leg_removes_both_and_reverts_both_accounts(): void
    {
        $from = Account::factory()->withInitialBalance(1000)->create();
        $to = Account::factory()->withInitialBalance(0)->create();

        $legs = app(TransferBetweenAccounts::class)->handle(
            from: $from,
            to: $to,
            amount: Money::fromDecimal(400, 'ARS'),
        );

        app(DeleteTransaction::class)->handle($legs['out']);

        $this->assertSame(100000, $from->fresh()->current_balance->minorUnits);
        $this->assertSame(0, $to->fresh()->current_balance->minorUnits);
        $this->assertSame(0, Transaction::count());
    }
}
