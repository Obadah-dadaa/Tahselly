<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SystemSetting;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        config([
            'services.stripe.secret' => 'sk_test_dummy',
            'services.stripe.webhook_secret' => self::WEBHOOK_SECRET,
            'services.payment_sandbox' => false,
        ]);
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response(['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.com/c/pay/cs_test_123']),
        ]);
    }

    private function startCheckout(): Subscription
    {
        $this->post(route('subscribe.store', 'agency-pro'), [
            'cycle' => 'yearly',
            'payment_method' => 'card',
            'company_name' => 'Stripe Co',
            'name' => 'Owner',
            'email' => 'owner@stripe.test',
            'phone' => '0501234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ])->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

        return Subscription::query()->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function paidSession(Subscription $subscription, array $overrides = []): array
    {
        return array_merge([
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'client_reference_id' => (string) $subscription->id,
            'amount_total' => (int) round((float) $subscription->amount * 100),
            'currency' => 'aed',
        ], $overrides);
    }

    private function postWebhook(array $event, ?string $secret = null): TestResponse
    {
        $payload = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret ?? self::WEBHOOK_SECRET);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_checkout_session_is_created_with_the_plan_price(): void
    {
        $subscription = $this->startCheckout();

        $this->assertSame('cs_test_123', $subscription->gateway_ref);
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
            && $request['line_items'][0]['price_data']['unit_amount'] === 199000
            && $request['line_items'][0]['price_data']['currency'] === 'aed'
            && (string) $request['client_reference_id'] === (string) $subscription->id);
    }

    public function test_signed_webhook_activates_the_subscription(): void
    {
        $subscription = $this->startCheckout();

        $this->postWebhook(['type' => 'checkout.session.completed', 'data' => ['object' => $this->paidSession($subscription)]])->assertOk();

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('active', $subscription->company->fresh()->status);
    }

    public function test_webhook_with_a_wrong_signature_is_rejected(): void
    {
        $subscription = $this->startCheckout();

        $this->postWebhook(['type' => 'checkout.session.completed', 'data' => ['object' => $this->paidSession($subscription)]], 'whsec_wrong')
            ->assertStatus(400);

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_webhook_with_a_different_amount_does_not_activate(): void
    {
        $subscription = $this->startCheckout();

        $this->postWebhook(['type' => 'checkout.session.completed', 'data' => ['object' => $this->paidSession($subscription, ['amount_total' => 100])]])->assertOk();

        $this->assertSame('pending', $subscription->fresh()->status);
    }

    public function test_return_page_confirms_payment_and_signs_the_owner_in(): void
    {
        $subscription = $this->startCheckout();
        Http::fake(['api.stripe.com/v1/checkout/sessions/cs_test_123' => Http::response($this->paidSession($subscription))]);

        $this->get(route('subscribe.return', $subscription).'?session_id=cs_test_123')->assertRedirect(route('owner.billing'));

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertAuthenticated();
    }

    public function test_card_checkout_can_be_hidden_from_settings(): void
    {
        SystemSetting::query()->where('key', 'card_payments')->update(['value' => '0']);
        SystemSetting::flush();

        $this->get(route('subscribe.show', 'agency-pro'))->assertOk()->assertDontSee('value="card"', false);
        $this->post(route('subscribe.store', 'agency-pro'), [
            'cycle' => 'monthly', 'payment_method' => 'card', 'company_name' => 'Hidden Co', 'name' => 'Owner',
            'email' => 'owner@hidden.test', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', 'terms' => 1,
        ])->assertSessionHasErrors('payment_method');
    }
}
