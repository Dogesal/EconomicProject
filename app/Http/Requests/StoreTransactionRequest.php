<?php

namespace App\Http\Requests;

use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
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
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['required', 'date'],
        ];
    }

    /**
     * An expense cannot leave the account in the red. Auto-generated
     * recurring transactions bypass this on purpose (they don't go
     * through this request).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty() || $this->input('type') !== 'expense') {
                    return;
                }

                $account = Account::find($this->input('account_id'));

                if ($account === null) {
                    return;
                }

                $amount = Money::fromDecimal($this->input('amount'), $account->currency);

                if ($amount->minorUnits > $account->current_balance->minorUnits) {
                    $validator->errors()->add(
                        'amount',
                        'Saldo insuficiente en la cuenta ('.$account->current_balance->format().').'
                    );
                }
            },
        ];
    }
}
