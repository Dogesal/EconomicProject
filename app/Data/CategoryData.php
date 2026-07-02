<?php

namespace App\Data;

use App\Domain\Models\Category;
use Spatie\LaravelData\Data;

class CategoryData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public ?string $parentId,
        public ?string $icon,
        public ?string $color,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            name: $category->name,
            type: $category->type->value,
            parentId: $category->parent_id,
            icon: $category->icon,
            color: $category->color,
        );
    }
}
