<?php

namespace Database\Factories;

use App\Domain\Enums\CategoryType;
use App\Domain\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Comida', 'Transporte', 'Servicios', 'Ocio', 'Salud', 'Sueldo']),
            'type' => fake()->randomElement(CategoryType::cases()),
            'parent_id' => null,
            'icon' => null,
            'color' => fake()->hexColor(),
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Income]);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Expense]);
    }
}
