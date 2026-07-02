<?php

namespace Tests\Feature;

use App\Application\Debts\RecordDebtPayment;
use App\Application\Transactions\DeleteTransaction;
use App\Domain\Enums\DebtStatus;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Debt;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DebtTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_debt_can_be_created_from_the_http_endpoint(): void
    {
        $this->post(route('debts.store'), [
            'name' => 'Préstamo banco',
            'direction' => 'i_owe',
            'amount' => 1500.50,
            'currency' => 'ARS',
            'due_date' => now()->addMonth()->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');

        $debt = Debt::sole();

        $this->assertSame('Préstamo banco', $debt->name);
        $this->assertSame(150050, $debt->original_amount->minorUnits);
        $this->assertSame(0, $debt->paid_amount->minorUnits);
        $this->assertSame(DebtStatus::Active, $debt->status);
    }

    public function test_paying_a_debt_creates_a_linked_expense_and_lowers_the_account_balance(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $debt = Debt::factory()->iOwe()->amount(400)->create();

        $this->post(route('debts.pay', $debt), [
            'account_id' => $account->id,
            'amount' => 150,
            'occurred_on' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHas('success');

        $debt->refresh();
        $account->refresh();

        $this->assertSame(15000, $debt->paid_amount->minorUnits);
        $this->assertSame(DebtStatus::Active, $debt->status);
        $this->assertSame(85000, $account->current_balance->minorUnits);

        $payment = $debt->payments()->sole();
        $this->assertSame(TransactionType::Expense, $payment->type);
        $this->assertFalse($payment->is_inflow);
        $this->assertSame('Pago de deuda: '.$debt->name, $payment->description);
    }

    public function test_paying_the_full_amount_settles_the_debt(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $debt = Debt::factory()->iOwe()->amount(400)->create();

        app(RecordDebtPayment::class)->handle($debt, $account, Money::fromDecimal(400, 'ARS'));

        $this->assertSame(DebtStatus::Settled, $debt->refresh()->status);
        $this->assertSame(0, $debt->remaining()->minorUnits);
        $this->assertSame(100.0, $debt->progressPercentage());
    }

    public function test_collecting_a_debt_owed_to_me_creates_an_income(): void
    {
        $account = Account::factory()->withInitialBalance(100)->create();
        $debt = Debt::factory()->owedToMe()->amount(300)->create();

        $this->post(route('debts.pay', $debt), [
            'account_id' => $account->id,
            'amount' => 300,
            'occurred_on' => now()->toDateString(),
        ])->assertRedirect();

        $payment = $debt->payments()->sole();

        $this->assertSame(TransactionType::Income, $payment->type);
        $this->assertTrue($payment->is_inflow);
        $this->assertSame(40000, $account->refresh()->current_balance->minorUnits);
        $this->assertSame(DebtStatus::Settled, $debt->refresh()->status);
    }

    public function test_a_payment_in_another_currency_is_rejected(): void
    {
        $account = Account::factory()->currency('USD')->create();
        $debt = Debt::factory()->iOwe()->amount(100, 'ARS')->create();

        $this->expectException(InvalidArgumentException::class);

        app(RecordDebtPayment::class)->handle($debt, $account, Money::fromDecimal(50, 'ARS'));
    }

    public function test_paying_a_settled_debt_is_rejected(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $debt = Debt::factory()->iOwe()->amount(100)->settled()->create();

        $this->expectException(InvalidArgumentException::class);

        app(RecordDebtPayment::class)->handle($debt, $account, Money::fromDecimal(10, 'ARS'));
    }

    public function test_deleting_the_payment_transaction_reopens_the_debt(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $debt = Debt::factory()->iOwe()->amount(200)->create();

        $payment = app(RecordDebtPayment::class)->handle($debt, $account, Money::fromDecimal(200, 'ARS'));
        $this->assertSame(DebtStatus::Settled, $debt->refresh()->status);

        app(DeleteTransaction::class)->handle($payment);

        $debt->refresh();
        $this->assertSame(0, $debt->paid_amount->minorUnits);
        $this->assertSame(DebtStatus::Active, $debt->status);
        $this->assertSame(100000, $account->refresh()->current_balance->minorUnits);
    }

    public function test_paying_more_than_the_remaining_amount_is_rejected(): void
    {
        $account = Account::factory()->withInitialBalance(1000)->create();
        $debt = Debt::factory()->iOwe()->amount(250)->create();

        $this->post(route('debts.pay', $debt), [
            'account_id' => $account->id,
            'amount' => 300,
            'occurred_on' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, $debt->refresh()->paid_amount->minorUnits);
        $this->assertSame(100000, $account->refresh()->current_balance->minorUnits);
    }

    public function test_paying_a_debt_with_more_than_the_account_balance_is_rejected(): void
    {
        $account = Account::factory()->withInitialBalance(50)->create();
        $debt = Debt::factory()->iOwe()->amount(400)->create();

        $this->post(route('debts.pay', $debt), [
            'account_id' => $account->id,
            'amount' => 100,
            'occurred_on' => now()->toDateString(),
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, $debt->refresh()->paid_amount->minorUnits);
    }

    public function test_collecting_an_owed_debt_is_not_capped_by_the_account_balance(): void
    {
        // Money comes IN when collecting, so a low balance must not block it.
        $account = Account::factory()->withInitialBalance(0)->create();
        $debt = Debt::factory()->owedToMe()->amount(300)->create();

        $this->post(route('debts.pay', $debt), [
            'account_id' => $account->id,
            'amount' => 300,
            'occurred_on' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(30000, $account->refresh()->current_balance->minorUnits);
    }

    public function test_the_debts_page_renders_with_summary_totals(): void
    {
        Debt::factory()->iOwe()->amount(500)->create();
        Debt::factory()->owedToMe()->amount(200)->create();
        Debt::factory()->iOwe()->amount(100)->settled()->create();

        $this->get(route('debts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Debts/Index')
                ->has('debts', 3)
                ->has('summary.iOwe', 1)
                ->has('summary.owedToMe', 1)
                ->where('summary.iOwe.0.minorUnits', 50000)
                ->where('summary.owedToMe.0.minorUnits', 20000));
    }
}
