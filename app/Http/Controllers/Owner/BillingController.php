<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Support\Edition;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $request->user()->company->load('plan');
        if (Edition::isBeta()) {
            return view('owner.beta-access', ['company' => $company, 'welcome' => false]);
        }

        return view('owner.billing', [
            'company' => $company,
            'current' => $company->currentSubscription(),
            'pending' => $company->subscriptions()->with('plan')->where('status', 'pending')->latest()->first(),
            'history' => $company->subscriptions()->with('plan')->latest()->get(),
            'plans' => Plan::query()->where('is_active', true)->ordered()->get(),
        ]);
    }
}
