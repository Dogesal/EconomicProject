<?php

namespace App\Data;

use App\Domain\ValueObjects\Money;
use Spatie\LaravelData\Data;

class MoneyData extends Data
{
    public function __construct(
        public int $minorUnits,
        public string $currency,
        public float $decimal,
        public string $formatted,
    ) {}

    public static function fromMoney(Money $money): self
    {
        return new self(
            minorUnits: $money->minorUnits,
            currency: $money->currency,
            decimal: $money->toDecimal(),
            formatted: $money->format(),
        );
    }
}
