<?php

namespace App\Http\Controllers;

use App\Data\AccountData;
use App\Data\CategoryData;
use App\Data\RecurringTransactionData;
use App\Domain\Models\Category;
use App\Domain\Models\Currency;
use App\Domain\Models\RecurringTransaction;
use App\Domain\Models\Setting;
use App\Infrastructure\Repositories\Contracts\AccountRepository;
use App\Support\AppLock;
use App\Support\AppTheme;
use App\Support\DisplayCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(AccountRepository $accounts, DisplayCurrency $displayCurrency, AppLock $lock): Response
    {
        return Inertia::render('Settings/Index', [
            'displayCurrency' => $displayCurrency->resolve(),
            'appLockEnabled' => $lock->isEnabled(),
            'appLockHasPin' => $lock->hasPin(),
            'currencies' => Currency::orderBy('code')->get(['code', 'name', 'symbol']),
            'accounts' => AccountData::collect($accounts->allActive()),
            'categories' => CategoryData::collect(Category::orderBy('name')->get()),
            'recurring' => RecurringTransactionData::collect(
                RecurringTransaction::with('account')->orderBy('next_run_on')->get()
            ),
        ]);
    }

    public function updateCurrency(): RedirectResponse
    {
        $data = request()->validate([
            'display_currency' => ['required', 'string', Rule::exists('currencies', 'code')],
        ]);

        Setting::put(DisplayCurrency::SETTING_KEY, strtoupper($data['display_currency']));

        return back()->with('success', 'Moneda de visualización actualizada.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', 'string', Rule::in(AppTheme::OPTIONS)],
        ]);

        Setting::put(AppTheme::SETTING_KEY, $data['theme']);

        return back()->with('success', match ($data['theme']) {
            'dark' => 'Tema oscuro activado.',
            'light' => 'Tema claro activado.',
            default => 'El tema seguirá al sistema.',
        });
    }

    public function updateLock(Request $request, AppLock $lock): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        if ($data['enabled'] && ! $lock->hasPin()) {
            // A backup PIN guarantees the user can always get back in even
            // if biometrics are unavailable on the device.
            return back()->withErrors(['enabled' => 'Configurá primero un PIN de respaldo.']);
        }

        $lock->setEnabled($data['enabled']);

        if (! $data['enabled']) {
            // Turning the lock off should not strand the user behind it.
            $lock->unlock($request);
        }

        return back()->with('success', $data['enabled'] ? 'Bloqueo activado.' : 'Bloqueo desactivado.');
    }

    public function updatePin(Request $request, AppLock $lock): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'digits_between:4,6', 'confirmed'],
            'current_pin' => ['nullable', 'digits_between:4,6'],
        ]);

        // Changing an existing PIN while the lock is armed requires the old one.
        if ($lock->hasPin() && $lock->isEnabled() && ! $lock->checkPin((string) $request->input('current_pin'))) {
            return back()->withErrors(['current_pin' => 'El PIN actual no es correcto.']);
        }

        $lock->setPin($request->string('pin'));

        return back()->with('success', 'PIN guardado.');
    }
}
