<?php

namespace App\Http\Controllers;

use App\Domain\Enums\CategoryType;
use App\Domain\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create([
            'name' => $request->string('name'),
            'type' => $request->enum('type', CategoryType::class),
            'color' => $request->input('color'),
            'icon' => $request->input('icon'),
        ]);

        return back()->with('success', 'Categoría creada.');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update([
            'name' => $request->string('name'),
            'color' => $request->input('color'),
            'icon' => $request->input('icon'),
        ]);

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        DB::transaction(function () use ($category) {
            // Budgets are meaningless without their category; transactions
            // survive and simply show up as "uncategorized".
            $category->budgets()->get()->each->delete();
            $category->delete();
        });

        return back()->with('success', 'Categoría eliminada.');
    }
}
