<?php

namespace App\Application\WhatsApp;

use App\Domain\Enums\DebtStatus;
use App\Domain\Enums\TransactionType;
use App\Domain\Models\Account;
use App\Domain\Models\Category;
use App\Domain\Models\Debt;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;

/**
 * Snapshot que la app sube al servidor del bot en cada sync: cuentas con
 * saldo, categorías, deudas activas y un resumen del mes. Con esto el bot
 * resuelve cuentas/categorías, valida montos y responde consultas sin
 * conocer nada más de las finanzas del usuario.
 */
class AccountsSnapshot
{
    /**
     * @return array{
     *     accounts: list<array{id: string, name: string, balance: float, currency: string}>,
     *     categories: list<array{name: string, type: string}>,
     *     debts: list<array{name: string, remaining: float, currency: string, direction: string}>,
     *     summary: array{month: string, by_currency: list<array{currency: string, income: float, expense: float}>, top_categories: list<array{name: string, amount: float, currency: string}>}
     * }
     */
    public static function build(): array
    {
        return [
            'accounts' => self::accounts(),
            'categories' => self::categories(),
            'debts' => self::debts(),
            'summary' => self::summary(),
        ];
    }

    /**
     * @return list<array{id: string, name: string, balance: float, currency: string}>
     */
    private static function accounts(): array
    {
        return Account::where('is_archived', false)
            ->get()
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => $account->current_balance->toDecimal(),
                'currency' => $account->currency,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, type: string}>
     */
    private static function categories(): array
    {
        return Category::orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn (Category $category): array => [
                'name' => $category->name,
                'type' => $category->type->value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, remaining: float, currency: string, direction: string}>
     */
    private static function debts(): array
    {
        return Debt::where('status', DebtStatus::Active)
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(fn (Debt $debt): array => [
                'name' => $debt->name,
                'remaining' => $debt->remaining()->toDecimal(),
                'currency' => $debt->currency,
                'direction' => $debt->direction->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Totales del mes en curso por moneda y top de categorías de gasto.
     * Las transferencias no cuentan: mueven dinero, no lo crean ni gastan.
     *
     * @return array{month: string, by_currency: list<array{currency: string, income: float, expense: float}>, top_categories: list<array{name: string, amount: float, currency: string}>}
     */
    private static function summary(): array
    {
        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();

        $byCurrency = Transaction::query()
            ->where('type', '!=', TransactionType::Transfer)
            ->whereBetween('occurred_on', [$start, $end])
            ->selectRaw('currency, sum(case when is_inflow then amount else 0 end) as income_minor, sum(case when not is_inflow then amount else 0 end) as expense_minor')
            ->groupBy('currency')
            ->orderBy('currency')
            ->limit(20)
            ->get()
            ->map(fn ($row): array => [
                'currency' => $row->currency,
                'income' => Money::fromMinor((int) $row->income_minor, $row->currency)->toDecimal(),
                'expense' => Money::fromMinor((int) $row->expense_minor, $row->currency)->toDecimal(),
            ])
            ->values()
            ->all();

        $topCategories = Transaction::query()
            ->where('transactions.type', TransactionType::Expense)
            ->whereBetween('occurred_on', [$start, $end])
            ->whereNotNull('category_id')
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->selectRaw('categories.name as name, transactions.currency as currency, sum(transactions.amount) as total_minor')
            ->groupBy('categories.name', 'transactions.currency')
            ->orderByDesc('total_minor')
            ->limit(5)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'amount' => Money::fromMinor((int) $row->total_minor, $row->currency)->toDecimal(),
                'currency' => $row->currency,
            ])
            ->values()
            ->all();

        return [
            'month' => now()->format('Y-m'),
            'by_currency' => $byCurrency,
            'top_categories' => $topCategories,
        ];
    }
}
