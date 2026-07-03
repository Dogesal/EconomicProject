<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class WeekdaySpendingData extends Data
{
    public function __construct(
        public int $weekday,
        public string $label,
        public MoneyData $total,
    ) {}
}
