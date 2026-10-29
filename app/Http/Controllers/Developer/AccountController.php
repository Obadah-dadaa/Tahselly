<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Developer-only account administration: suspend, reactivate or permanently delete
 * customer users and companies. Platform staff accounts are never touched here.
 */
class AccountController extends Controller
{
    public function toggleUser(User $user): RedirectResponse
    {
        abort_if($user->isPlatformStaff(), 404);

        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            $this->endSessions($user);
        }
        ActivityLog::record($user->is_active ? 'user.activated' : 'user.deactivated', ($user->is_active ? 'تفعيل حساب ' : 'إيقاف حساب ').$user->email, [], $user->company_id);

        return back()->with('success', $user->is_active
            ? __('تم تفعيل حساب :email.', ['email' => $user->email])
            : __('تم إيقاف حساب :email وتسجيل خروجه.', ['email' => $user->email]));
    }

    public function destroyUser(User $user): RedirectResponse
    {
        abort_if($user->isPlatformStaff(), 404);

        $email = $user->email;
        $companyId = $user->company_id;
        $this->endSessions($user);
        $user->delete();
        ActivityLog::record('user.deleted', 'حذف المستخدم '.$email, [], $companyId);

        return back()->with('success', __('تم حذف المستخدم :email.', ['email' => $email]));
    }

    public function toggleCompany(Company $company): RedirectResponse
    {
        $suspend = $company->status !== 'suspended';
        // Reactivating restores trial or paid access depending on what the company had
        $company->update(['status' => $suspend ? 'suspended' : ($company->subscriptions()->current()->exists() ? 'active' : ($company->trial_ends_at?->isFuture() ? 'trial' : 'expired'))]);
        if ($suspend) {
            $company->users->each(fn (User $u) => $this->endSessions($u));
        }
        ActivityLog::record($suspend ? 'company.suspended' : 'company.reactivated', ($suspend ? 'إيقاف الشركة ' : 'إعادة تفعيل الشركة ').$company->name, [], $company->id);

        return back()->with('success', $suspend
            ? __('تم إيقاف :company وتسجيل خروج كل مستخدميها.', ['company' => $company->name])
            : __('تمت إعادة تفعيل :company.', ['company' => $company->name]));
    }

    public function destroyCompany(Request $request, Company $company): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'string']]);
        if (trim((string) $request->input('confirm')) !== $company->name) {
            return back()->withErrors(['confirm' => __('اكتب اسم الشركة كما هو تماماً لتأكيد الحذف.')]);
        }

        $name = $company->name;
        DB::transaction(function () use ($company): void {
            $company->users->each(fn (User $u) => $this->endSessions($u));
            // Users, clients, invoices, payments, reminders and subscriptions cascade with the company
            $company->delete();
        });
        ActivityLog::record('company.deleted', 'حذف الشركة نهائياً: '.$name);

        return redirect()->route('developer.companies.index')->with('success', __('تم حذف :company وكل بياناتها نهائياً.', ['company' => $name]));
    }

    private function endSessions(User $user): void
    {
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
