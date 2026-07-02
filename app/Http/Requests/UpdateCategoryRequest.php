<?php

namespace App\Http\Requests;

use App\Domain\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The category type is intentionally immutable: changing it would break
     * existing budgets and transaction filters that rely on it.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories', 'name')
                    ->where('type', $category->type->value)
                    ->ignore($category->id)
                    ->withoutTrashed(),
            ],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:10'],
        ];
    }
}
