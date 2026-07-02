<?php

namespace App\Http\Controllers;

use App\Support\AppLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LockController extends Controller
{
    public function __construct(private AppLock $lock) {}

    public function show(Request $request): Response|RedirectResponse
    {
        if (! $this->lock->isEnabled() || $this->lock->isUnlocked($request)) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Lock', [
            'appName' => config('app.name'),
        ]);
    }

    public function unlock(Request $request): RedirectResponse
    {
        if ($request->filled('pin')) {
            $data = $request->validate([
                'pin' => ['required', 'digits_between:4,6'],
            ]);

            if (! $this->lock->checkPin($data['pin'])) {
                return back()->withErrors(['pin' => 'PIN incorrecto.']);
            }
        }

        // Without a PIN the biometric result was verified on the client
        // (privacy gate for a single-user on-device app, not a crypto boundary).
        $this->lock->unlock($request);

        return redirect()->route('dashboard');
    }

    /**
     * Drops the session unlock flag and sends the user to the lock screen.
     * Called by the frontend when the app cold-starts or comes back from
     * background. When the lock is disabled, `show` bounces back home.
     */
    public function relock(Request $request): RedirectResponse
    {
        $this->lock->lock($request);

        return redirect()->route('lock.show');
    }
}
