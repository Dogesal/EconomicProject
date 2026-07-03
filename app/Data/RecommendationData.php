<?php

namespace App\Data;

use Spatie\LaravelData\Data;

/**
 * A rule-based spending recommendation. Severity is one of
 * danger | warning | info and drives the accent color in the UI.
 */
class RecommendationData extends Data
{
    public function __construct(
        public string $type,
        public string $severity,
        public string $title,
        public string $message,
        public ?string $categoryId,
    ) {}
}
