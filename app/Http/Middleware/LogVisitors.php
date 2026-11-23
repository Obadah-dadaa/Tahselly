<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * shortcut: one-week log of every request that reaches Laravel (any path, method or status) to identify
 * the unlabelled night-time traffic; stops by itself on 2026-11-30, then delete this class and its line in bootstrap/app.php
 */
class LogVisitors
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (today()->lt('2026-11-30')) {
            try {
                $referrer = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
                Log::build(['driver' => 'daily', 'path' => storage_path('logs/visitors.log'), 'days' => 10])->info('visit', [
                    'method' => $request->method(),
                    'status' => $response->getStatusCode(),
                    'path' => mb_substr($request->getRequestUri(), 0, 300),
                    'user' => (bool) $request->user(),
                    'referrer' => $referrer && $referrer !== $request->getHost() ? $referrer : null,
                    'agent' => mb_substr((string) $request->userAgent(), 0, 300),
                ]);
            } catch (Throwable) {
            }
        }

        return $response;
    }
}
