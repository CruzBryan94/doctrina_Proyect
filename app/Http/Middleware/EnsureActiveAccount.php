<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        // A previously authenticated session must lose module access when deactivated.
        // This runs before route permission checks, including Spatie's Gate callbacks.
        if (! $request->routeIs('welcome', 'logout')) {
            abort_unless($request->user()?->is_active, 403, 'La cuenta está inactiva.');
        }

        return $next($request);
    }
}
