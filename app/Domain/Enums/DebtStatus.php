<?php

namespace App\Domain\Enums;

enum DebtStatus: string
{
    case Active = 'active';
    case Settled = 'settled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Settled => 'Saldada',
        };
    }
}
