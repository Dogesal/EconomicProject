<?php

namespace App\Http\Requests;

use App\Domain\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', new Enum(AccountType::class)],
            'color' => ['nullable', 'string', 'max:20'],
            'is_archived' => ['boolean'],
        ];
    }
}
