<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's language (ar/en) and theme (light/dark) to every web request.
 * The choice lives in the session and in a year-long cookie, so it survives signing out,
 * session expiry and new visits; a signed-in user's saved choice comes next.
 */
class SetPreferences
{
    public const COOKIE_MINUTES = 60 * 24 * 365;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        // ?lang=ar|en gives each language its own URL for search engines (hreflang); the visitor keeps it
        if (in_array($request->query('lang'), ['ar', 'en'], true)) {
            $request->session()->put('locale', $request->query('lang'));
        }
        $locale = $request->session()->get('locale') ?? $request->cookie('locale') ?? $user?->locale ?? config('app.locale');
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'en';
        app()->setLocale($locale);
        // New accounts start with the language they were browsing in
        if ($user && ! $user->locale) {
            $user->forceFill(['locale' => $locale])->saveQuietly();
        }

        $theme = $request->session()->get('theme') ?? $request->cookie('theme') ?? $user?->theme ?? 'light';
        View::share('theme', $theme === 'dark' ? 'dark' : 'light');
        View::share('dir', app()->getLocale() === 'ar' ? 'rtl' : 'ltr');

        return $next($request);
    }
}
