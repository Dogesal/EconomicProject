<?php

namespace App\Http\Requests;

use App\Domain\Enums\RecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRecurringTransactionRequest extends FormRequest
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
            'account_id' => ['required', 'uuid', Rule::exists('accounts', 'id')],
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', new Enum(RecurrenceFrequency::class)],
            'interval' => ['required', 'integer', 'min:1', 'max:365'],
            'next_run_on' => ['required', 'date'],
            'end_on' => ['nullable', 'date', 'after_or_equal:next_run_on'],
        ];
    }
}
