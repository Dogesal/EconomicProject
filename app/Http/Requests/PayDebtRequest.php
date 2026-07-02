<?php

namespace App\Http\Requests;

use App\Domain\Enums\DebtDirection;
use App\Domain\Models\Account;
use App\Domain\Models\Debt;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

                /** @var Debt $debt */
                $debt = $this->route('debt');
                $amount = Money::fromDecimal($this->input('amount'), $debt->currency);

                if ($amount->minorUnits > $debt->remaining()->minorUnits) {
                    $validator->errors()->add(
                        'amount',
                        'El pago supera lo que falta: '.$debt->remaining()->format().'.'
                    );

                    return;
                }

                // Money only leaves the account when paying own debts.
                if ($debt->direction === DebtDirection::IOwe) {
                    $account = Account::find($this->input('account_id'));

                    if ($account && $amount->minorUnits > $account->current_balance->minorUnits) {
                        $validator->errors()->add(
                            'amount',
                            'Saldo insuficiente en la cuenta ('.$account->current_balance->format().').'
                        );
                    }
                }
            },
        ];
    }
}
