<?php

namespace App\Domain\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Gasto',
            self::Transfer => 'Transferencia',
        };
    }

    /**
     * Whether, by default, a transaction of this type increases the account balance.
     * Transfers are resolved per-leg via the transaction's own `is_inflow` flag.
     */
    public function defaultIsInflow(): bool
    {
        return $this === self::Income;
    }
}
