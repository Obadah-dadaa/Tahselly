<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $request->user()->company->load('plan');

        return view('owner.billing', [
            'company' => $company,
            'current' => $company->currentSubscription(),
            'history' => $company->subscriptions()->with('plan')->latest()->get(),
            'plans' => Plan::query()->where('is_active', true)->ordered()->get(),
        ]);
    }
}
