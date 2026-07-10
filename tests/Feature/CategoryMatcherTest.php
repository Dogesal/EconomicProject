<?php

namespace Tests\Feature;

use App\Application\WhatsApp\CategoryMatcher;
use App\Domain\Enums\CategoryType;
use App\Domain\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_exact_name_ignoring_case_and_accents(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Educación']);

        $match = app(CategoryMatcher::class)->match('educacion', CategoryType::Expense);

        $this->assertTrue($match->category?->is($category));
        $this->assertNull($match->description);
    }

    public function test_matches_first_token_and_keeps_rest_as_description(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Comida']);

        $match = app(CategoryMatcher::class)->match('comida con amigos', CategoryType::Expense);

        $this->assertTrue($match->category?->is($category));
        $this->assertSame('con amigos', $match->description);
    }

    public function test_prefers_full_text_match_over_first_token(): void
    {
        Category::factory()->expense()->create(['name' => 'Comida']);
        $fullMatch = Category::factory()->expense()->create(['name' => 'Comida rápida']);

        $match = app(CategoryMatcher::class)->match('comida rapida', CategoryType::Expense);

        $this->assertTrue($match->category?->is($fullMatch));
        $this->assertNull($match->description);
    }

    public function test_only_matches_categories_of_the_requested_type(): void
    {
        Category::factory()->expense()->create(['name' => 'Sueldo extra']);
        $income = Category::factory()->income()->create(['name' => 'Sueldo']);

        $match = app(CategoryMatcher::class)->match('sueldo', CategoryType::Income);

        $this->assertTrue($match->category?->is($income));
    }

    public function test_no_match_keeps_text_as_description(): void
    {
        Category::factory()->expense()->create(['name' => 'Comida']);

        $match = app(CategoryMatcher::class)->match('tragamonedas', CategoryType::Expense);

        $this->assertNull($match->category);
        $this->assertSame('tragamonedas', $match->description);
    }

    public function test_empty_text_returns_nothing(): void
    {
        $match = app(CategoryMatcher::class)->match(null, CategoryType::Expense);

        $this->assertNull($match->category);
        $this->assertNull($match->description);
    }
}
