<?php

namespace Database\Seeders;

use App\Application\Budgets\CreateBudget;
use App\Application\Debts\CreateDebt;
use App\Application\Goals\CreateGoal;
use App\Application\Transactions\RecordTransaction;
use App\Domain\Enums\AccountType;
use App\Domain\Enums\DebtDirection;
use App\Domain\Enums\RecurrenceFrequency;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\ExchangeRate;
use App\Domain\Models\RecurringTransaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Database\Seeder;

/**
 * Sample data for local development only. The mobile app never runs this:
 * on-device installs start clean with just the catalog (see CatalogSeeder).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $currency = 'PEN';

        ExchangeRate::updateOrCreate(
            ['base_currency' => 'USD', 'quote_currency' => 'PEN', 'effective_on' => now()->toDateString()],
            ['rate' => 3.75],
        );

        $salary = Category::where('name', 'Sueldo')->first();
        $food = Category::where('name', 'Comida')->first();
        $transport = Category::where('name', 'Transporte')->first();
        $utilities = Category::where('name', 'Servicios')->first();

        $cash = Account::create([
            'name' => 'Efectivo',
            'type' => AccountType::Cash,
            'currency' => $currency,
            'initial_balance' => Money::fromDecimal(500, $currency),
            'current_balance' => Money::fromDecimal(500, $currency),
            'color' => '#22c55e',
        ]);

        $bank = Account::create([
            'name' => 'Banco',
            'type' => AccountType::Bank,
            'currency' => $currency,
            'initial_balance' => Money::fromDecimal(3000, $currency),
            'current_balance' => Money::fromDecimal(3000, $currency),
            'color' => '#4f46e5',
        ]);

        $record = app(RecordTransaction::class);

        $record->handle($bank, TransactionType::Income, Money::fromDecimal(3500, $currency), $salary, 'Sueldo del mes');
        $record->handle($cash, TransactionType::Expense, Money::fromDecimal(25, $currency), $food, 'Almuerzo');
        $record->handle($cash, TransactionType::Expense, Money::fromDecimal(5, $currency), $transport, 'Pasaje');
        $record->handle($bank, TransactionType::Expense, Money::fromDecimal(200, $currency), $utilities, 'Luz y agua');

        $budget = app(CreateBudget::class);
        $year = (int) now()->year;
        $month = (int) now()->month;

        $budget->handle($food, $year, $month, Money::fromDecimal(600, $currency));
        $budget->handle($utilities, $year, $month, Money::fromDecimal(150, $currency)); // Excedido a propósito.
        $budget->handle($transport, $year, $month, Money::fromDecimal(120, $currency));

        app(CreateGoal::class)->handle('Fondo de emergencia', Money::fromDecimal(5000, $currency));

        $debt = app(CreateDebt::class);

        $debt->handle('Préstamo del banco', DebtDirection::IOwe, Money::fromDecimal(2500, $currency), now()->addMonths(6));
        $debt->handle('Préstamo a Carlos', DebtDirection::OwedToMe, Money::fromDecimal(300, $currency));

        RecurringTransaction::create([
            'account_id' => $bank->id,
            'category_id' => $utilities->id,
            'type' => TransactionType::Expense,
            'amount' => Money::fromDecimal(1200, $currency),
            'currency' => $currency,
            'description' => 'Alquiler',
            'frequency' => RecurrenceFrequency::Monthly,
            'interval' => 1,
            'next_run_on' => now()->addMonth()->startOfMonth()->toDateString(),
        ]);
    }
}
