<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordUpdated
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->must_change_password && ! $request->routeIs('password.change', 'password.change.*', 'logout')) {
            return redirect()->route('password.change.show');
        }

        return $next($request);
    }
}
