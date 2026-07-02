<?php

namespace App\Domain\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Gasto',
        };
    }

    public function toTransactionType(): TransactionType
    {
        return match ($this) {
            self::Income => TransactionType::Income,
            self::Expense => TransactionType::Expense,
        };
    }
}
