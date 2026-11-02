<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns away crawlers that harvest pages for AI training (robots.txt asks them first;
 * this stops the ones that don't listen). robots.txt itself stays readable.
 */
class BlockAiTrainingBots
{
    public function handle(Request $request, Closure $next): Response
    {
        $agent = strtolower((string) $request->userAgent());

        if ($agent !== '' && ! $request->is('robots.txt')) {
            foreach (config('tahselly.ai_training_bots', []) as $bot) {
                if (str_contains($agent, strtolower($bot))) {
                    abort(403);
                }
            }
        }

        return $next($request);
    }
}
