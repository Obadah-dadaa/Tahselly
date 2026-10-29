<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('بيانات الدخول غير صحيحة.')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        ActivityLog::record('login', 'تسجيل دخول');

        // Trial owners land on the trial welcome page every time they sign in
        // (unless they were on their way to confirm their email, which leads there anyway)
        $intended = (string) $request->session()->get('url.intended', '');
        if ($request->user()->onTrialAsOwner() && ! str_contains($intended, '/email/verify/')) {
            $request->session()->forget('url.intended');

            return redirect()->route('owner.plan');
        }

        return redirect()->intended(route($request->user()->role->homeRoute()));
    }

    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isOwner() && $user->company && ! $user->company->canUse('owner_analytics')) {
            return redirect()->route('dashboard');
        }

        return redirect()->route($user->role->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
