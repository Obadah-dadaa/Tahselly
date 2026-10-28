<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company->load('plan');

        return view('owner.team', [
            'users' => User::query()->where('company_id', $company->id)->orderBy('role')->get(),
            'company' => $company,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->company->load('plan');
        $limit = $company->plan?->max_users ?? 3;
        if ($company->users()->count() >= $limit) {
            return back()->with('error', __('وصلت للحد الأقصى للمستخدمين في باقتك (:limit). قم بالترقية لإضافة المزيد.', ['limit' => $limit]));
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['owner', 'manager'])],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create($data + ['company_id' => $company->id, 'is_active' => true]);
        $user->markEmailAsVerified();
        ActivityLog::record('team.added', 'إضافة مستخدم: '.$user->name);

        return back()->with('success', __('تمت إضافة المستخدم.'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->company_id === $request->user()->company_id, 404);
        abort_if($user->is($request->user()), 422, __('لا يمكنك تعطيل حسابك.'));

        $data = $request->validate([
            'role' => ['required', Rule::in(['owner', 'manager'])],
            'is_active' => ['required', 'boolean'],
        ]);
        $user->update($data);
        ActivityLog::record('team.updated', 'تحديث صلاحيات: '.$user->name);

        return back()->with('success', __('تم التحديث.'));
    }
}
