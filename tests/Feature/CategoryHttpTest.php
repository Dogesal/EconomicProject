<?php

namespace Tests\Feature;

use App\Domain\Models\Budget;
use App\Domain\Models\Category;
use App\Domain\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_expense_category_can_be_created(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Mascotas',
            'type' => 'expense',
            'color' => '#f97316',
            'icon' => '🐶',
        ])->assertRedirect()->assertSessionHas('success');

        $category = Category::sole();

        $this->assertSame('Mascotas', $category->name);
        $this->assertSame('expense', $category->type->value);
        $this->assertSame('🐶', $category->icon);
    }

    public function test_an_income_category_can_be_created(): void
    {
        $this->post(route('categories.store'), [
            'name' => 'Freelance',
            'type' => 'income',
        ])->assertRedirect();

        $this->assertSame('income', Category::sole()->type->value);
    }

    public function test_a_duplicate_name_within_the_same_type_is_rejected(): void
    {
        Category::factory()->expense()->create(['name' => 'Comida']);

        $this->post(route('categories.store'), ['name' => 'Comida', 'type' => 'expense'])
            ->assertSessionHasErrors('name');

        // The same name is fine for the other type.
        $this->post(route('categories.store'), ['name' => 'Comida', 'type' => 'income'])
            ->assertSessionDoesntHaveErrors('name');
    }

    public function test_a_category_can_be_renamed_and_recolored(): void
    {
        $category = Category::factory()->expense()->create(['name' => 'Ocio']);

        $this->put(route('categories.update', $category), [
            'name' => 'Entretenimiento',
            'color' => '#9333ea',
            'icon' => '🎮',
        ])->assertRedirect()->assertSessionHas('success');

        $category->refresh();

        $this->assertSame('Entretenimiento', $category->name);
        $this->assertSame('#9333ea', $category->color);
    }

    public function test_deleting_a_category_removes_its_budgets_and_orphans_transactions(): void
    {
        $category = Category::factory()->expense()->create();
        $budget = Budget::factory()->create(['category_id' => $category->id]);
        $transaction = Transaction::factory()->expense()->create(['category_id' => $category->id]);

        $this->delete(route('categories.destroy', $category))->assertRedirect();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);

        // The transaction survives and now renders as "uncategorized"
        // because the category relation is filtered by soft deletes.
        $this->assertNull($transaction->fresh()->category);
    }

    public function test_the_deleted_category_no_longer_appears_in_settings(): void
    {
        $category = Category::factory()->expense()->create();

        $this->delete(route('categories.destroy', $category));

        $this->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('categories', 0));
    }
}
