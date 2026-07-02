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
            'is_archived' => $request->boolean('is_archived'),
        ]);

        return back()->with('success', 'Cuenta creada.');
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update([
            'name' => $request->string('name'),
            'type' => $request->enum('type', AccountType::class),
            'color' => $request->input('color'),
            'is_archived' => $request->boolean('is_archived'),
        ]);

        return back()->with('success', 'Cuenta actualizada.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $account->delete();

        return back()->with('success', 'Cuenta eliminada.');
    }
}
