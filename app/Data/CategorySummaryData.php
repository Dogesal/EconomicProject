<?php

namespace App\Data;

use App\Domain\Models\Category;
use Spatie\LaravelData\Data;

/**
 * Lightweight category shape for embedding in transaction lists.
 */
class CategorySummaryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $icon,
        public ?string $color,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            icon: $category->icon,
            color: $category->color,
        );
    }
}
