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
        $plans = Plan::query()->where('is_active', true)->orderBy('sort')->get();

        return view('subscribe.trial', [
            'plans' => $plans,
            'selected' => $plans->firstWhere('code', $request->plan)?->code ?? $plans->firstWhere('is_featured', true)?->code ?? $plans->first()?->code,
            'days' => (int) SystemSetting::get('default_trial_days', 14),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(SystemSetting::get('allow_signups', true), 404);
        $data = $request->validate([
            'plan' => ['required', Rule::exists('plans', 'code')->where('is_active', true)],
            'company_name' => ['required', 'string', 'max:190'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'terms' => ['accepted'],
        ]);

        $plan = Plan::query()->where('code', $data['plan'])->firstOrFail();
        $days = max(1, (int) SystemSetting::get('default_trial_days', 14));

        $owner = DB::transaction(function () use ($data, $plan, $days): User {
            $company = Company::create([
                'name' => $data['company_name'],
                'category' => $plan->category,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'plan_id' => $plan->id,
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

        ActivityLog::record('trial.started', "بدء تجربة مجانية ({$days} يوم): {$plan->name}", [], $owner->company_id);
        $owner->sendEmailVerificationNotification();
        Auth::login($owner);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', __('أهلاً بك! تجربتك المجانية فعّالة حتى :date. ابدأ بإضافة عملائك أو استيراد فواتيرك.', ['date' => $owner->company->trial_ends_at->format('Y-m-d')]));
    }
}
