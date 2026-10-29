<?php

namespace App\Http\Middleware;

use App\Models\Plan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a route unless the company's plan (or running free trial) includes the feature.
 * Pages show an upgrade screen; form posts go back with an upgrade message.
 */
class RequirePlanFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $company = $request->user()?->company;
        if (! $company || $company->canUse($feature)) {
            return $next($request);
        }

        $plan = Plan::minimumFor($feature);
        $message = __('«:feature» متاحة من باقة :plan. قم بترقية باقتك لاستخدامها.', [
            'feature' => Plan::featureLabel($feature),
            'plan' => $plan?->localizedName() ?? '',
        ]);

        if ($request->isMethod('GET') && ! $request->expectsJson()) {
            return response()->view('upgrade', ['feature' => $feature, 'plan' => $plan, 'message' => $message], 403);
        }

        return back()->with('error', $message);
    }
}
