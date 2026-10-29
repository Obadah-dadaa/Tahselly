<?php

namespace App\Http\Controllers\Developer;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        return view('developer.companies.index', [
            'companies' => Company::query()->with('plan')->withCount(['users', 'invoices', 'clients'])
                ->when($request->q, fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(20)->withQueryString(),
            'plans' => Plan::query()->where('is_active', true)->ordered()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'plan_id' => ['required', 'exists:plans,id'],
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'unique:users,email'],
            'owner_password' => ['required', Password::min(8)],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);

        $trialDays = (int) ($data['trial_days'] ?? 14);
        $company = Company::create([
            'name' => $data['name'],
            'email' => $data['owner_email'],
            'plan_id' => $data['plan_id'],
            'status' => $trialDays > 0 ? 'trial' : 'active',
            'trial_ends_at' => $trialDays > 0 ? today()->addDays($trialDays) : null,
        ]);
        $owner = User::create([
            'company_id' => $company->id,
            'name' => $data['owner_name'],
            'email' => $data['owner_email'],
            'password' => $data['owner_password'],
            'role' => Role::Owner,
        ]);
        $owner->markEmailAsVerified();
        ActivityLog::record('company.created', 'إنشاء شركة جديدة: '.$company->name, [], $company->id);

        return redirect()->route('developer.companies.show', $company)->with('success', __('تم إنشاء الشركة وحساب المالك.'));
    }

    public function show(Company $company): View
    {
        $company->load(['plan', 'users']);

        return view('developer.companies.show', [
            'company' => $company,
            'plans' => Plan::all(),
            'stats' => [
                'clients' => $company->clients()->count(),
                'invoices' => Invoice::query()->where('company_id', $company->id)->count(),
                'outstanding' => (float) Invoice::query()->where('company_id', $company->id)->open()->selectRaw('SUM(amount - paid_amount) as s')->value('s'),
                'reminders' => Reminder::query()->where('company_id', $company->id)->count(),
            ],
            'activity' => ActivityLog::query()->where('company_id', $company->id)->with('user')->latest()->limit(15)->get(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['trial', 'active', 'suspended'])],
            'trial_ends_at' => ['nullable', 'date'],
        ]);
        $company->update($data);
        ActivityLog::record('company.updated', "تحديث اشتراك {$company->name}: {$data['status']}", [], $company->id);

        return back()->with('success', __('تم تحديث الاشتراك.'));
    }
}
