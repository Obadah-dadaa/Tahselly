<?php

namespace App\Http\Middleware;

use App\Support\Edition;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Plans, checkout and payment exist only on the live site; the beta answers 404.
 */
class LiveOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(Edition::isBeta(), 404);

        return $next($request);
    }
}
