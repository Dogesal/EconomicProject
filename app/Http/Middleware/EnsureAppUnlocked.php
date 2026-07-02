<?php

namespace App\Http\Middleware;

use App\Support\AppLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppUnlocked
{
    public function __construct(private AppLock $lock) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->lock->isEnabled() || $this->lock->isUnlocked($request)) {
            return $next($request);
        }

        // The lock screen and its unlock endpoint must stay reachable while locked.
        if ($request->routeIs('lock.show', 'lock.unlock')) {
            return $next($request);
        }

        return redirect()->route('lock.show');
    }
}
