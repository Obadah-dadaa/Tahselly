<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * Public checkout: pick a plan and cycle, create the account, pay, become a subscriber.
 * A signed-in owner uses the same flow to renew or change plan.
 */
class SubscribeController extends Controller
{
    public function show(Request $request, Plan $plan, PaymentGateway $gateway): View|RedirectResponse
    {
        abort_unless($plan->is_active, 404);
        $user = $request->user();
        if ($user && ! $user->isOwner()) {
            return redirect()->route('home')->with('error', __('الاشتراك متاح لمالك الشركة فقط.'));
        }

        $cycle = in_array($request->cycle, array_keys(Subscription::CYCLE_MONTHS), true) ? $request->cycle : 'monthly';

        return view('subscribe.checkout', [
            'plan' => $plan,
            'cycle' => $cycle,
            'methods' => $gateway->availableMethods(),
            'sandbox' => $gateway->sandboxEnabled() && ! $gateway->stripeEnabled(),
            'renewing' => (bool) $user,
            'current' => $user?->company?->currentSubscription(),
        ]);
    }

    public function store(Request $request, Plan $plan, PaymentGateway $gateway): RedirectResponse
    {
        abort_unless($plan->is_active, 404);
        $user = $request->user();
        $rules = [
            'cycle' => ['required', Rule::in(array_keys(Subscription::CYCLE_MONTHS))],
            'payment_method' => ['required', Rule::in($gateway->availableMethods())],
        ];
        if (! $user) {
            $rules += [
                'company_name' => ['required', 'string', 'max:190'],
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:190', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:30'],
                'password' => ['required', 'confirmed', Password::min(8)],
                'terms' => ['accepted'],
            ];
        }
        $data = $request->validate($rules);

        $subscription = DB::transaction(function () use ($user, $data, $plan): Subscription {
            $company = $user?->company;
            if (! $company) {
                // Account exists but cannot be used until the first payment is confirmed
                $company = Company::create([
                    'name' => $data['company_name'],
                    'category' => $plan->category,
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'plan_id' => $plan->id,
                    'status' => 'expired',
                ]);
                $owner = User::create([
                    'company_id' => $company->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'password' => $data['password'],
                    'role' => Role::Owner,
                    'locale' => app()->getLocale(),
                    'is_active' => false,
                ]);
                $owner->sendEmailVerificationNotification();
            }

            return $company->subscriptions()->create([
                'plan_id' => $plan->id,
                'cycle' => $data['cycle'],
                'amount' => $plan->priceFor($data['cycle']),
                'currency' => 'AED',
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
            ]);
        });

        $request->session()->put('checkout_subscription_id', $subscription->id);
        ActivityLog::record('subscription.started', "طلب اشتراك: {$plan->name} ({$data['cycle']})", [], $subscription->company_id);

        if ($data['payment_method'] === 'bank_transfer') {
            return redirect()->route('subscribe.pending', $subscription);
        }

        try {
            return redirect()->away($gateway->checkoutUrl($subscription));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', __('تعذر الاتصال ببوابة الدفع. حاول مرة أخرى أو اختر التحويل البنكي.'));
        }
    }

    /**
     * Local test card page (only when PAYMENT_SANDBOX is on and Stripe is not configured).
     */
    public function sandbox(Request $request, Subscription $subscription, PaymentGateway $gateway): View
    {
        $this->authorizeCheckout($request, $subscription);
        abort_unless($gateway->sandboxEnabled() && $subscription->status === 'pending', 404);

        return view('subscribe.sandbox', ['subscription' => $subscription->load('plan', 'company')]);
    }

    public function sandboxPay(Request $request, Subscription $subscription, PaymentGateway $gateway): RedirectResponse
    {
        $this->authorizeCheckout($request, $subscription);
        abort_unless($gateway->sandboxEnabled() && $subscription->status === 'pending', 404);

        $data = $request->validate([
            'card_number' => ['required', 'string'],
            'expiry' => ['required', 'string', 'max:7'],
            'cvc' => ['required', 'digits_between:3,4'],
        ]);
        $digits = preg_replace('/\D/', '', $data['card_number']);
        if ($digits === '4000000000000002' || strlen($digits) < 15) {
            return back()->with('error', __('تم رفض البطاقة (بطاقة اختبار مرفوضة).'));
        }

        return $this->complete($request, $subscription, 'sandbox_'.substr($digits, -4).'_'.now()->timestamp);
    }

    /**
     * Stripe redirects here after payment.
     */
    public function stripeReturn(Request $request, Subscription $subscription, PaymentGateway $gateway): RedirectResponse
    {
        $this->authorizeCheckout($request, $subscription);
        if ($subscription->status === 'active') {
            return $this->complete($request, $subscription, $subscription->gateway_ref);
        }
        if (! $gateway->confirmStripe($subscription, (string) $request->query('session_id'))) {
            return redirect()->route('subscribe.show', ['plan' => $subscription->plan->code, 'cycle' => $subscription->cycle])
                ->with('error', __('لم يتم تأكيد الدفع بعد.'));
        }

        return $this->complete($request, $subscription, $subscription->gateway_ref);
    }

    public function pending(Request $request, Subscription $subscription): View
    {
        $this->authorizeCheckout($request, $subscription);

        return view('subscribe.pending', [
            'subscription' => $subscription->load('plan', 'company'),
            'bank' => [
                'name' => SystemSetting::get('bank_name'),
                'account' => SystemSetting::get('bank_account_name'),
                'iban' => SystemSetting::get('bank_iban'),
            ],
        ]);
    }

    private function complete(Request $request, Subscription $subscription, ?string $reference): RedirectResponse
    {
        // The Stripe webhook may activate the same subscription concurrently; lock so it happens once
        $activatedHere = DB::transaction(function () use ($subscription, $reference): bool {
            $locked = Subscription::query()->lockForUpdate()->findOrFail($subscription->id);
            if ($locked->status !== 'pending') {
                return false;
            }
            $locked->activate($reference);

            return true;
        });
        if ($activatedHere) {
            ActivityLog::record('subscription.paid', "دفع اشتراك {$subscription->plan->name}: ".number_format((float) $subscription->amount, 2).' AED', [], $subscription->company_id);
        }

        $owner = $subscription->company->users()->where('role', Role::Owner)->first();
        if ($owner && ! Auth::check()) {
            Auth::login($owner);
            $request->session()->regenerate();
        }
        $request->session()->forget('checkout_subscription_id');

        return redirect()->route('owner.billing')->with('success', __('تم الدفع بنجاح! اشتراكك فعّال حتى :date.', ['date' => $subscription->fresh()->ends_at->format('Y-m-d')]));
    }

    /**
     * Only the browser that started the checkout, or the company's own owner, may continue it.
     */
    private function authorizeCheckout(Request $request, Subscription $subscription): void
    {
        $ownsSession = (int) $request->session()->get('checkout_subscription_id') === $subscription->id;
        $isOwner = $request->user()?->isOwner() && $request->user()->company_id === $subscription->company_id;
        abort_unless($ownsSession || $isOwner, 403);
    }
}
