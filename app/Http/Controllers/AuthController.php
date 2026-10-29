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

        return redirect()->intended(route($request->user()->role->homeRoute()));
    }

    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();
        // Trial owners who haven't picked a plan see the plans once per session (with "skip for now")
        if ($user->isOwner() && $user->company?->onTrial() && ! $user->company->plan_id && ! $request->session()->get('plan_prompted')) {
            $request->session()->put('plan_prompted', true);

            return redirect()->route('owner.plan');
        }
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
