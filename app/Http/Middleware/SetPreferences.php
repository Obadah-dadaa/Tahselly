<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the visitor's language (ar/en) and theme (light/dark) to every web request.
 */
class SetPreferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $locale = $request->session()->get('locale', $user?->locale ?? config('app.locale'));
        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        $theme = $request->session()->get('theme', $user?->theme ?? 'light');
        View::share('theme', $theme === 'dark' ? 'dark' : 'light');
        View::share('dir', app()->getLocale() === 'ar' ? 'rtl' : 'ltr');

        return $next($request);
    }
}
