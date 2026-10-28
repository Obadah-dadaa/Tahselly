<?php

namespace App\Services;

use App\Models\Subscription;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Card payments through Stripe Checkout (hosted page), a local sandbox for testing,
 * and manual bank transfer approved from the platform panel.
 */
class PaymentGateway
{
    /** Seconds a webhook signature stays valid (Stripe's default tolerance). */
    private const WEBHOOK_TOLERANCE = 300;

    public function stripeEnabled(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    public function sandboxEnabled(): bool
    {
        return (bool) config('services.payment_sandbox') && ! app()->isProduction();
    }

    /**
     * live | test (Stripe test keys) | sandbox (local simulator) | off (bank transfer only).
     */
    public function mode(): string
    {
        if ($this->stripeEnabled()) {
            return str_starts_with((string) config('services.stripe.secret'), 'sk_live_') || str_starts_with((string) config('services.stripe.secret'), 'rk_live_') ? 'live' : 'test';
        }

        return $this->sandboxEnabled() ? 'sandbox' : 'off';
    }

    /**
     * @return list<string>
     */
    public function availableMethods(): array
    {
        $methods = [];
        if ($this->stripeEnabled() || $this->sandboxEnabled()) {
            $methods[] = 'card';
        }
        $methods[] = 'bank_transfer';

        return $methods;
    }

    /**
     * Where to send the payer for a card payment.
     */
    public function checkoutUrl(Subscription $subscription): string
    {
        if (! $this->stripeEnabled()) {
            if (! $this->sandboxEnabled()) {
                throw new RuntimeException('No card gateway configured.');
            }
            $subscription->update(['payment_method' => 'sandbox']);

            return route('subscribe.sandbox', $subscription);
        }

        $subscription->loadMissing(['plan', 'company.users']);
        $response = $this->stripe()
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'locale' => 'auto',
                'client_reference_id' => $subscription->id,
                'customer_email' => $subscription->company->users->first()?->email,
                'metadata' => ['subscription_id' => $subscription->id, 'company_id' => $subscription->company_id],
                'payment_intent_data' => ['metadata' => ['subscription_id' => $subscription->id]],
                'success_url' => route('subscribe.return', $subscription).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('subscribe.show', ['plan' => $subscription->plan->code, 'cycle' => $subscription->cycle]),
                'line_items' => [[
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($subscription->currency),
                        'unit_amount' => $this->minorUnits($subscription),
                        'product_data' => ['name' => 'Tahseel — '.($subscription->plan->name_en ?: $subscription->plan->name).' ('.$subscription->cycle.')'],
                    ],
                ]],
            ])
            ->throw()
            ->json();

        $subscription->update(['gateway_ref' => $response['id']]);

        return $response['url'];
    }

    /**
     * Confirm with Stripe that the checkout session was paid (used on the return redirect).
     */
    public function confirmStripe(Subscription $subscription, string $sessionId): bool
    {
        if (! $this->stripeEnabled() || $sessionId !== $subscription->gateway_ref) {
            return false;
        }
        $session = $this->stripe()->get('https://api.stripe.com/v1/checkout/sessions/'.$sessionId)->throw()->json();

        return $this->sessionPaysFor($session, $subscription);
    }

    /**
     * A checkout session counts only if it is paid, belongs to this subscription and charged the full amount.
     *
     * @param  array<string, mixed>  $session
     */
    public function sessionPaysFor(array $session, Subscription $subscription): bool
    {
        return ($session['payment_status'] ?? null) === 'paid'
            && (string) ($session['client_reference_id'] ?? '') === (string) $subscription->id
            && (int) ($session['amount_total'] ?? -1) === $this->minorUnits($subscription)
            && strtolower((string) ($session['currency'] ?? '')) === strtolower($subscription->currency);
    }

    /**
     * Verify a webhook's Stripe-Signature header and return the decoded event, or null when it is not genuine.
     *
     * @return array<string, mixed>|null
     */
    public function verifyWebhook(string $payload, ?string $signatureHeader): ?array
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '' || ! $signatureHeader) {
            return null;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && $value) {
                $signatures[] = $value;
            }
        }
        if (! $timestamp || ! $signatures || abs(time() - $timestamp) > self::WEBHOOK_TOLERANCE) {
            return null;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return json_decode($payload, true) ?: null;
            }
        }

        return null;
    }

    /**
     * Check the configured key against Stripe for the platform panel.
     *
     * @return array{ok: bool, message: string}
     */
    public function testConnection(): array
    {
        if (! $this->stripeEnabled()) {
            return ['ok' => false, 'message' => 'STRIPE_SECRET is not set'];
        }
        try {
            $account = $this->stripe()->get('https://api.stripe.com/v1/account')->throw()->json();

            return ['ok' => true, 'message' => trim(($account['settings']['dashboard']['display_name'] ?? $account['id'] ?? 'Stripe').' · '.strtoupper((string) ($account['country'] ?? '')).' · charges '.(($account['charges_enabled'] ?? false) ? 'enabled' : 'not enabled yet'))];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    private function minorUnits(Subscription $subscription): int
    {
        return (int) round((float) $subscription->amount * 100);
    }

    private function stripe(): PendingRequest
    {
        return Http::withToken((string) config('services.stripe.secret'))->timeout(20)
            // retry only network failures, never a request Stripe already answered
            ->retry(2, 300, fn (Throwable $e) => $e instanceof ConnectionException, throw: false);
    }
}
