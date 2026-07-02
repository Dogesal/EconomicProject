<?php

namespace App\Http\Requests;

use App\Domain\Models\SavingsGoal;
use App\Domain\ValueObjects\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Shared by goals.contribute and goals.withdraw; the route decides which
 * amount cap applies.
 */
class ContributeGoalRequest extends FormRequest
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

                /** @var SavingsGoal $goal */
                $goal = $this->route('goal');
                $amount = Money::fromDecimal($this->input('amount'), $goal->currency);

                if ($this->routeIs('goals.withdraw')) {
                    if ($amount->minorUnits > $goal->current_amount->minorUnits) {
                        $validator->errors()->add(
                            'amount',
                            'El retiro supera lo ahorrado ('.$goal->current_amount->format().').'
                        );
                    }

                    return;
                }

                // Contributions from a linked account are capped by its balance.
                $account = $goal->account;

                if ($account !== null && $amount->minorUnits > $account->current_balance->minorUnits) {
                    $validator->errors()->add(
                        'amount',
                        'Saldo insuficiente en la cuenta ('.$account->current_balance->format().').'
                    );
                }
            },
        ];
    }
}
