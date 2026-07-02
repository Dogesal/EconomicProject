<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class MonthlyEvolutionPointData extends Data
{
    public function __construct(
        public int $year,
        public int $month,
        public string $label,
        public MoneyData $income,
        public MoneyData $expense,
        public MoneyData $net,
    ) {}
}
