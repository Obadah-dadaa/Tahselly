<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetPreferences;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Language and light/dark theme switches: stored in the session, and on the user when signed in.
 */
class PreferenceController extends Controller
{
    public function locale(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);
        $request->user()?->forceFill(['locale' => $locale])->save();

        return back()->withCookie(cookie('locale', $locale, SetPreferences::COOKIE_MINUTES));
    }

    public function theme(Request $request, string $theme): RedirectResponse
    {
        $request->session()->put('theme', $theme);
        $request->user()?->forceFill(['theme' => $theme])->save();

        return back()->withCookie(cookie('theme', $theme, SetPreferences::COOKIE_MINUTES));
    }
}
