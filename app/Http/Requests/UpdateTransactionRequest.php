<?php

namespace App\Http\Requests;

use App\Domain\Enums\TransactionType;
use App\Domain\Models\Transaction;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0'],
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')],
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

                /** @var Transaction $transaction */
                $transaction = $this->route('transaction');

                if ($transaction->type !== TransactionType::Expense || $transaction->account === null) {
                    return;
                }

                // Editing an expense frees up its own current amount first.
                $available = $transaction->account->current_balance->minorUnits + $transaction->amount->minorUnits;
                $amount = Money::fromDecimal($this->input('amount'), $transaction->currency);

                if ($amount->minorUnits > $available) {
                    $validator->errors()->add(
                        'amount',
                        'Saldo insuficiente en la cuenta ('.Money::fromMinor($available, $transaction->currency)->format().' disponibles).'
                    );
                }
            },
        ];
    }
}
