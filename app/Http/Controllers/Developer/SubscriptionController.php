<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\AccountNotice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Every subscriber of the platform: plan, cycle, period and payment, with approvals.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $subscriptions = Subscription::query()
            ->with(['company', 'plan'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->cycle, fn ($q, $c) => $q->where('cycle', $c))
            ->when($request->plan_id, fn ($q, $p) => $q->where('plan_id', $p))
            ->when($request->category, fn ($q, $c) => $q->whereHas('plan', fn ($p) => $p->where('category', $c)))
            ->when($request->expiring, fn ($q) => $q->current()->where('ends_at', '<=', now()->addDays(14)))
            ->when($request->q, fn ($q, $term) => $q->whereHas('company', fn ($c) => $c->where('name', 'like', "%{$term}%")))
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'active' THEN 1 WHEN 'expired' THEN 2 ELSE 3 END")
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $active = Subscription::query()->current()->get();

        return view('developer.subscriptions', [
            'subscriptions' => $subscriptions,
            'plans' => Plan::query()->ordered()->get(),
            'stats' => [
                'active' => $active->count(),
                'mrr' => $active->sum(fn (Subscription $s) => $s->monthlyValue()),
                'revenue_month' => (float) Subscription::query()->whereNotNull('paid_at')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
                'pending' => Subscription::query()->where('status', 'pending')->count(),
                'expiring' => $active->filter(fn (Subscription $s) => $s->ends_at->lte(now()->addDays(14)))->count(),
            ],
            'byPlan' => $active->groupBy('plan_id')->map->count(),
            'byCycle' => $active->groupBy('cycle')->map->count(),
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function approve(Subscription $subscription): RedirectResponse
    {
        abort_unless($subscription->status === 'pending', 422);
        $subscription->activate($subscription->gateway_ref ?? 'bank-'.$subscription->id);
        AccountNotice::send(AccountNotice::owners($subscription->company), 'subscription_active', AccountNotice::about($subscription->fresh()), route('owner.billing'));
        ActivityLog::record('subscription.approved', 'اعتماد تحويل بنكي: '.$subscription->company->name, [], $subscription->company_id);

        return back()->with('success', __('تم اعتماد الدفع وتفعيل الاشتراك.'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['extend', 'cancel'])],
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $company = $subscription->company;

        if ($data['action'] === 'extend') {
            $months = (int) ($data['months'] ?? 1);
            $base = $subscription->ends_at && $subscription->ends_at->isFuture() ? $subscription->ends_at : now();
            $subscription->update(['status' => 'active', 'ends_at' => $base->copy()->addMonths($months)]);
            $subscription->company->update(['status' => 'active']);
            ActivityLog::record('subscription.extended', "تمديد اشتراك {$subscription->company->name} {$months} شهر", [], $subscription->company_id);

            AccountNotice::send(AccountNotice::owners($company), 'subscription_extended', AccountNotice::about($subscription->fresh()), route('owner.billing'));

            return back()->with('success', __('تم تمديد اشتراك :company :months شهر.', ['company' => $company->name, 'months' => $months]));
        }

        // Cancelling ends access now, unless the company still has another paid period running
        $subscription->update(['status' => 'cancelled', 'ends_at' => now()]);
        if (! $company->subscriptions()->current()->exists()) {
            $company->update(['status' => 'expired']);
        }
        AccountNotice::send(AccountNotice::owners($company), 'subscription_cancelled', AccountNotice::about($subscription), route('owner.billing'));
        ActivityLog::record('subscription.cancelled', 'إلغاء اشتراك '.$company->name.($data['reason'] ? ' — '.$data['reason'] : ''), [], $company->id);

        return back()->with('success', __('تم إلغاء اشتراك :company وإيقاف وصولها للنظام.', ['company' => $company->name]));
    }

    /**
     * Record a subscription manually (paid offline, gift, partner deal).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'cycle' => ['required', Rule::in(array_keys(Subscription::CYCLE_MONTHS))],
        ]);
        $plan = Plan::query()->findOrFail($data['plan_id']);
        $subscription = Subscription::create($data + [
            'amount' => $plan->priceFor($data['cycle']),
            'status' => 'pending',
            'payment_method' => 'manual',
        ]);
        $subscription->activate('manual-'.$request->user()->id);
        AccountNotice::send(AccountNotice::owners($subscription->company), 'subscription_active', AccountNotice::about($subscription->fresh()), route('owner.billing'));
        ActivityLog::record('subscription.manual', 'اشتراك يدوي: '.$subscription->company->name, [], $subscription->company_id);

        return back()->with('success', __('تم إضافة الاشتراك.'));
    }
}
