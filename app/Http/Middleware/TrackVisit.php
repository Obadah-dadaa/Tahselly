<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Counts unique daily visitors to the public pages for the platform dashboard.
 * The visitor key is a daily-salted hash, so no IP address or cookie is kept
 * and the same person cannot be followed from one day to the next.
 */
class TrackVisit
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|headless|lighthouse|monitor/i';

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldCount($request, $response)) {
            try {
                $day = today()->toDateString();
                DB::table('site_visits')->insertOrIgnore([
                    'day' => $day,
                    'visitor' => hash_hmac('sha256', $request->ip().'|'.$request->userAgent().'|'.$day, (string) config('app.key')),
                    'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 190),
                    'referrer' => $this->referrerHost($request),
                    'created_at' => now(),
                ]);
            } catch (Throwable) {
                // Analytics must never break a page
            }
        }

        // shortcut: one-week user-agent log to identify the unlabelled night-time traffic; stops by itself on 2026-11-30, then delete this block
        if ($request->isMethod('GET') && ! $request->user() && today()->lt('2026-11-30')) {
            try {
                Log::build(['driver' => 'daily', 'path' => storage_path('logs/visitors.log'), 'days' => 10])->info('visit', [
                    'counted' => $this->shouldCount($request, $response),
                    'status' => $response->getStatusCode(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'referrer' => $this->referrerHost($request),
                    'agent' => mb_substr((string) $request->userAgent(), 0, 300),
                ]);
            } catch (Throwable) {
            }
        }

        return $response;
    }

    private function shouldCount(Request $request, Response $response): bool
    {
        return $request->isMethod('GET')
            && $response->isSuccessful()
            && ! $request->user()
            && $request->header('Purpose') !== 'prefetch'
            && ! preg_match(self::BOTS, (string) $request->userAgent());
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return $host && $host !== $request->getHost() ? mb_substr($host, 0, 190) : null;
    }
}
