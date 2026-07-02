<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayDebtRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'occurred_on' => ['required', 'date'],
        ];
    }
}
