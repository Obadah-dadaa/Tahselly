<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InterestRequest;
use App\Notifications\AccountNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff inbox for "interested in subscribing" requests: mark handled, or give the company more time.
 */
class InterestController extends Controller
{
    public function index(): View
    {
        return view('developer.interest', [
            'requests' => InterestRequest::query()->with('company')->orderByRaw("status = 'new' desc")->latest()->paginate(30),
        ]);
    }

    public function handle(InterestRequest $interest): RedirectResponse
    {
        $interest->update(['status' => 'handled', 'handled_at' => now()]);

        return back()->with('success', __('تم تعليم الطلب كمُعالَج.'));
    }

    /**
     * Activate (extend) the requesting company's access by a number of days.
     */
    public function extend(Request $request, InterestRequest $interest): RedirectResponse
    {
        $days = (int) $request->validate(['days' => ['required', 'integer', 'in:30,90,180,365']])['days'];
        $company = $interest->company ?? abort(404);

        $from = $company->trial_ends_at && $company->trial_ends_at->isFuture() ? $company->trial_ends_at : today();
        $company->update(['status' => 'trial', 'trial_ends_at' => $from->copy()->addDays($days)]);
        $interest->update(['status' => 'handled', 'handled_at' => now()]);
        ActivityLog::record('company.extended', "تفعيل حساب {$company->name} لمدة {$days} يوم", [], $company->id);
        AccountNotice::send(AccountNotice::owners($company), 'access_extended', ['date' => $company->trial_ends_at->format('Y-m-d')], route('home'));

        return back()->with('success', __('تم تفعيل حساب :company حتى :date.', ['company' => $company->name, 'date' => $company->trial_ends_at->format('Y-m-d')]));
    }
}
