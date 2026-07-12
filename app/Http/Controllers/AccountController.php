<?php

namespace App\Http\Controllers;

use App\Data\AccountData;
use App\Domain\Enums\AccountType;
use App\Domain\Models\Account;
use App\Domain\ValueObjects\Money;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(AccountRepository $accounts): Response
    {
        return Inertia::render('Accounts/Index', [
            'accounts' => AccountData::collect($accounts->allActive()),
            'archivedAccounts' => AccountData::collect($accounts->archived()),
            'accountTypes' => collect(AccountType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $currency = strtoupper($request->string('currency'));
        $initial = Money::fromDecimal($request->input('initial_balance', 0), $currency);

        Account::create([
            'name' => $request->string('name'),
            'type' => $request->enum('type', AccountType::class),
            'currency' => $currency,
            'initial_balance' => $initial,
            'current_balance' => $initial,
            'color' => $request->input('color'),
        ]);

        return back()->with('success', 'Cuenta creada.');
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        // Una cuenta archivada es un registro de solo lectura: para
        // modificarla hay que restaurarla primero.
        if ($account->is_archived) {
            return back()->with('error', 'La cuenta está archivada. Restáurala para poder editarla.');
        }

        $account->update([
            'name' => $request->string('name'),
            'type' => $request->enum('type', AccountType::class),
            'color' => $request->input('color'),
        ]);

        return back()->with('success', 'Cuenta actualizada.');
    }

    /**
     * "Eliminar" preserva el historial: si la cuenta tiene movimientos se
     * archiva (queda como registro de solo lectura, conserva nombre y saldo,
     * y sus movimientos siguen mostrando la cuenta). Solo se borra de verdad
     * cuando está vacía.
     */
    public function destroy(Account $account): RedirectResponse
    {
        if ($account->transactions()->exists()) {
            $account->update(['is_archived' => true]);

            return back()->with('success', 'Cuenta archivada: conserva su historial pero ya no recibe movimientos.');
        }

        $account->forceDelete();

        return back()->with('success', 'Cuenta eliminada.');
    }

    public function restore(Account $account): RedirectResponse
    {
        $account->update(['is_archived' => false]);

        return back()->with('success', 'Cuenta restaurada.');
    }
}
