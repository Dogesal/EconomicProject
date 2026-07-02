<?php

namespace App\Domain\Enums;

enum DebtDirection: string
{
    case IOwe = 'i_owe';
    case OwedToMe = 'owed_to_me';

    public function label(): string
    {
        return match ($this) {
            self::IOwe => 'Debo',
            self::OwedToMe => 'Me deben',
        };
    }

    /**
     * Transaction type produced when paying off (IOwe) or collecting
     * (OwedToMe) this kind of debt.
     */
    public function paymentTransactionType(): TransactionType
    {
        return match ($this) {
            self::IOwe => TransactionType::Expense,
            self::OwedToMe => TransactionType::Income,
        };
    }
}
