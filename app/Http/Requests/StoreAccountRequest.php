<?php

namespace App\Http\Requests;

use App\Domain\Enums\AccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAccountRequest extends FormRequest
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
            'currency' => ['required', 'string', 'size:3'],
            'initial_balance' => ['nullable', 'numeric'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_archived' => ['boolean'],
        ];
    }
}
