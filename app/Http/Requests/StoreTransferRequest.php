<?php

namespace App\Http\Requests;

use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
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
            'from_account_id' => ['required', 'uuid', Rule::exists('accounts', 'id')],
            'to_account_id' => ['required', 'uuid', 'different:from_account_id', Rule::exists('accounts', 'id')],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['required', 'date'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $from = Account::find($this->input('from_account_id'));

                if ($from === null) {
                    return;
                }

                $amount = Money::fromDecimal($this->input('amount'), $from->currency);

                if ($amount->minorUnits > $from->current_balance->minorUnits) {
                    $validator->errors()->add(
                        'amount',
                        'Saldo insuficiente en la cuenta de origen ('.$from->current_balance->format().').'
                    );
                }
            },
        ];
    }
}
