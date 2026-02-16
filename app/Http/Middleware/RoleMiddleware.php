<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Usage: ->middleware('role:owner') or ->middleware('role:owner,admin')
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $role = $user->role ?? null;

        if (!$role || !in_array($role, $roles, true)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
