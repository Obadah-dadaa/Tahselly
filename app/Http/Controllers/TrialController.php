<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Free trial: a visitor creates an account without a card, uses the full product
 * for the trial period, then pays from the billing page to continue.
 */
class TrialController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless(SystemSetting::get('allow_signups', true), 404);

        return view('subscribe.trial', [
            // A plan picked on the pricing page is kept; otherwise the plan is chosen after sign-up
            'selected' => $request->plan ? Plan::query()->where('is_active', true)->where('code', $request->plan)->first() : null,
            'days' => (int) SystemSetting::get('default_trial_days', 14),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(SystemSetting::get('allow_signups', true), 404);
        $data = $request->validate([
            'plan' => ['nullable', Rule::exists('plans', 'code')->where('is_active', true)],
            'company_name' => ['required', 'string', 'max:190'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $plan = isset($data['plan']) ? Plan::query()->where('code', $data['plan'])->first() : null;
        $days = max(1, (int) SystemSetting::get('default_trial_days', 14));

        $owner = DB::transaction(function () use ($data, $plan, $days): User {
            $company = Company::create([
                'name' => $data['company_name'],
                'category' => $plan?->category,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'plan_id' => $plan?->id,
                'status' => 'trial',
                'trial_ends_at' => today()->addDays($days),
            ]);

            return User::create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => Role::Owner,
                'locale' => app()->getLocale(),
                'is_active' => true,
            ]);
        });

        ActivityLog::record('trial.started', "بدء تجربة مجانية ({$days} يوم)".($plan ? ": {$plan->name}" : ''), [], $owner->company_id);
        $owner->sendEmailVerificationNotification();
        Auth::login($owner);
        $request->session()->regenerate();

        $welcome = __('أهلاً بك! تجربتك المجانية فعّالة حتى :date. ابدأ بإضافة عملائك أو استيراد فواتيرك.', ['date' => $owner->company->trial_ends_at->format('Y-m-d')]);

        // No plan yet: show the plans once, with "skip for now"
        return redirect()->route('home')->with('success', $welcome);
    }

    public function choosePlan(Request $request): View|RedirectResponse
    {
        $company = $request->user()->company;
        if ($company->currentSubscription()) {
            return redirect()->route('owner.billing');
        }

        return view('owner.choose-plan', ['plans' => Plan::query()->where('is_active', true)->ordered()->get()]);
    }

    /**
     * Pick the plan the trial will continue into; everything stays open until the trial ends.
     */
    public function storePlan(Request $request): RedirectResponse
    {
        $company = $request->user()->company;
        abort_if((bool) $company->currentSubscription(), 403);
        $plan = Plan::query()->where('is_active', true)->where('code', (string) $request->input('plan'))->firstOrFail();

        $company->update(['plan_id' => $plan->id, 'category' => $plan->category]);
        ActivityLog::record('settings.plan', 'اختيار الباقة: '.$plan->name, [], $company->id);

        return redirect()->route('dashboard')->with('success', __('اخترت باقة :plan. كل المزايا تبقى مفتوحة حتى نهاية تجربتك.', ['plan' => $plan->localizedName()]));
    }
}
