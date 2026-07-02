<?php

namespace App\Domain\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case CreditCard = 'credit_card';
    case Savings = 'savings';
    case Investment = 'investment';
    case Wallet = 'wallet';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Bank => 'Cuenta bancaria',
            self::CreditCard => 'Tarjeta de crédito',
            self::Savings => 'Ahorros',
            self::Investment => 'Inversión',
            self::Wallet => 'Billetera digital',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknotes',
            self::Bank => 'building-library',
            self::CreditCard => 'credit-card',
            self::Savings => 'piggy-bank',
            self::Investment => 'chart-bar',
            self::Wallet => 'wallet',
        };
    }
}
