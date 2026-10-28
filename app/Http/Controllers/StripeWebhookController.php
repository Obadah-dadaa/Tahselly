<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Subscription;
use App\Services\PaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Stripe → Tahseel: activates a subscription when its Checkout payment completes,
 * even if the customer never returns to the success page.
 */
class StripeWebhookController extends Controller
{
    /** @var list<string> */
    private const PAID_EVENTS = ['checkout.session.completed', 'checkout.session.async_payment_succeeded'];

    public function __invoke(Request $request, PaymentGateway $gateway): JsonResponse
    {
        $event = $gateway->verifyWebhook($request->getContent(), $request->header('Stripe-Signature'));
        if (! $event) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        if (! in_array($event['type'] ?? '', self::PAID_EVENTS, true)) {
            return response()->json(['received' => true]);
        }

        $session = $event['data']['object'] ?? [];
        $activated = DB::transaction(function () use ($session, $gateway): ?Subscription {
            $subscription = Subscription::query()->lockForUpdate()->find((int) ($session['client_reference_id'] ?? 0));
            if (! $subscription || $subscription->status !== 'pending' || ! $gateway->sessionPaysFor($session, $subscription)) {
                return null;
            }
            $subscription->activate($session['id'] ?? null);

            return $subscription;
        });

        if ($activated) {
            ActivityLog::record('subscription.paid', 'دفع اشتراك عبر Stripe: '.number_format((float) $activated->amount, 2).' AED', ['session' => $session['id'] ?? null], $activated->company_id);
        }

        return response()->json(['received' => true]);
    }
}
