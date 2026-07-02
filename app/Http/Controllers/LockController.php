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
        // The biometric result is verified on the client before this is called;
        // on web (no biometrics) the fallback unlocks directly.
        $this->lock->unlock($request);

        return redirect()->route('dashboard');
    }
}
