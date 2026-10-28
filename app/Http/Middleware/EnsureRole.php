<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Allow only the listed roles; block deactivated users and suspended companies,
     * and send companies without a paid period to the billing page.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => __('الحساب غير مفعل.')]);
        }

        $company = $user->isPlatformStaff() ? null : $user->company;

        if ($company?->status === 'suspended') {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => __('اشتراك الشركة موقوف. تواصل مع الدعم.')]);
        }

        if ($company && ! $company->hasAccess() && ! $request->routeIs('owner.billing', 'subscribe.*')) {
            if ($user->isOwner()) {
                return redirect()->route('owner.billing')->with('error', __('انتهى اشتراككم. جدّد الاشتراك لمتابعة استخدام النظام.'));
            }
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => __('انتهى اشتراك الشركة. تواصل مع مالك الحساب لتجديده.')]);
        }

        if ($roles && ! $user->hasRole(...$roles)) {
            abort(403, __('غير مصرح لك بالدخول إلى هذه الصفحة.'));
        }

        return $next($request);
    }
}
