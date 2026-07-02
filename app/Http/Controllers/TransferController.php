<?php

namespace App\Http\Controllers;

use App\Application\Transactions\TransferBetweenAccounts;
use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\StoreTransferRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    public function store(StoreTransferRequest $request, TransferBetweenAccounts $transfer): RedirectResponse
    {
        $from = Account::findOrFail($request->string('from_account_id'));
        $to = Account::findOrFail($request->string('to_account_id'));

        if ($from->currency !== $to->currency) {
            throw ValidationException::withMessages([
                'to_account_id' => 'Por ahora solo se permiten transferencias entre cuentas de la misma moneda.',
            ]);
        }

        $transfer->handle(
            from: $from,
            to: $to,
            amount: Money::fromDecimal($request->input('amount'), $from->currency),
            description: $request->input('description'),
            occurredOn: $request->date('occurred_on'),
        );

        return back()->with('success', 'Transferencia realizada.');
    }
}
