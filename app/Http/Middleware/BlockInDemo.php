<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Actions a demo visitor can't take: inviting people, choosing or paying for a plan, sending verification mail.
 */
class BlockInDemo
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->company?->isDemo()) {
            return back()->with('error', __('هذه الخطوة غير متاحة في الحساب التجريبي. أنشئ حسابك المجاني لاستخدامها.'));
        }

        return $next($request);
    }
}
