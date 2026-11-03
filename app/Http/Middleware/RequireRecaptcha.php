<?php

namespace App\Http\Middleware;

use App\Support\Recaptcha;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public forms (sign-up, login, contact…) must carry a passing reCAPTCHA token for their action.
 * Does nothing until the keys are set.
 */
class RequireRecaptcha
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        if (Recaptcha::enabled() && ! Recaptcha::passes($request->input('g-recaptcha-response'), $action, $request->ip())) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'g-recaptcha-response'))
                ->withErrors(['recaptcha' => __('تعذّر التحقق من أنك لست روبوتاً. حدّث الصفحة وحاول مرة أخرى.')]);
        }

        return $next($request);
    }
}
