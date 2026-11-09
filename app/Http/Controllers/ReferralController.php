<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * Referral program: the share link that marks a visitor, and the owner's page with their link and results.
 */
class ReferralController extends Controller
{
    public function visit(Request $request, string $code): RedirectResponse
    {
        $code = strtoupper($code);
        if (Referral::enabled() && Company::query()->where('referral_code', $code)->exists()) {
            $request->session()->put(Referral::COOKIE, $code);
            Cookie::queue(Referral::COOKIE, $code, 60 * 24 * 30);
        }

        return redirect()->route('landing', ['utm_source' => 'referral']);
    }

    public function index(Request $request): View
    {
        abort_unless(Referral::enabled(), 404);
        $company = $request->user()->company;

        return view('owner.referrals', [
            'link' => Referral::link($company),
            'referrals' => $company->referrals()->latest()->get(['id', 'name', 'status', 'created_at', 'referral_rewarded_at']),
            'rewardDays' => Referral::rewardDays(),
            'trialDays' => Referral::trialDays(),
            'creditDays' => $company->referral_credit_days,
        ]);
    }
}
