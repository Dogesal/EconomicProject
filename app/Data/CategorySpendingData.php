<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CategorySpendingData extends Data
{
    public function __construct(
        public string $categoryId,
        public string $categoryName,
        public ?string $color,
        public MoneyData $total,
        public float $percentage,
    ) {}
}
