<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the platform area: anyone who is not active platform staff with one of the
 * listed roles (guests included) gets a plain 404, so the pages' existence is not revealed.
 */
class RequireStaff
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && $user->is_active && $user->isPlatformStaff() && (! $roles || $user->hasRole(...$roles)), 404);

        return $next($request);
    }
}
