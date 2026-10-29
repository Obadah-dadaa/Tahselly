<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        config(['services.payment_sandbox' => true, 'services.stripe.secret' => null]);
    }

    /**
     * @return array<string, string>
     */
    private function signup(string $method, string $email = 'new@biz.test'): array
    {
        return [
            'cycle' => 'yearly',
            'payment_method' => $method,
            'company_name' => 'New Biz',
            'name' => 'Owner Name',
            'email' => $email,
            'phone' => '0501234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ];
    }

    public function test_card_checkout_creates_an_active_subscriber(): void
    {
        $response = $this->post(route('subscribe.store', 'freelancer'), $this->signup('card'));
        $subscription = Subscription::query()->firstOrFail();
        $response->assertRedirect(route('subscribe.sandbox', $subscription));

        $this->post(route('subscribe.sandbox.pay', $subscription), ['card_number' => '4242 4242 4242 4242', 'expiry' => '12/28', 'cvc' => '123'])
            ->assertRedirect(route('owner.billing'));

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertEquals(Plan::query()->where('code', 'freelancer')->first()->priceFor('yearly'), $subscription->amount);
        $this->assertTrue($subscription->ends_at->between(now()->addMonths(12)->subMinute(), now()->addMonths(12)->addMinute()));
        $this->assertSame('active', $subscription->company->status);
        $this->assertAuthenticatedAs(User::query()->where('email', 'new@biz.test')->first());
    }

    public function test_declined_card_keeps_subscription_pending(): void
    {
        $this->post(route('subscribe.store', 'freelancer'), $this->signup('card'));
        $subscription = Subscription::query()->firstOrFail();

        $this->post(route('subscribe.sandbox.pay', $subscription), ['card_number' => '4000 0000 0000 0002', 'expiry' => '12/28', 'cvc' => '123'])
            ->assertSessionHas('error');

        $this->assertSame('pending', $subscription->fresh()->status);
        $this->assertGuest();
    }

    public function test_bank_transfer_waits_for_platform_approval(): void
    {
        $this->post(route('subscribe.store', 'accounting'), $this->signup('bank_transfer'))->assertRedirect();
        $subscription = Subscription::query()->firstOrFail();
        $owner = User::query()->where('email', 'new@biz.test')->first();
        $this->assertFalse($owner->is_active);

        $platform = User::factory()->create(['role' => Role::Platform]);
        $this->actingAs($platform)->post(route('developer.subscriptions.approve', $subscription))->assertSessionHas('success');

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_another_browser_cannot_open_someone_elses_checkout(): void
    {
        $this->post(route('subscribe.store', 'freelancer'), $this->signup('card'));
        $subscription = Subscription::query()->firstOrFail();

        $this->flushSession();
        $this->get(route('subscribe.sandbox', $subscription))->assertForbidden();
    }

    public function test_owner_of_expired_company_is_sent_to_billing(): void
    {
        $company = Company::factory()->create(['status' => 'expired']);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner]);
        $manager = User::factory()->create(['company_id' => $company->id, 'role' => Role::Manager]);

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('owner.billing'));
        $this->actingAs($owner)->get(route('owner.billing'))->assertOk();
        $this->actingAs($manager)->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_platform_owner_sees_subscribers_but_not_developer_tools(): void
    {
        $platform = User::factory()->create(['role' => Role::Platform]);

        $this->actingAs($platform)->get(route('developer.subscriptions.index'))->assertOk();
        $this->actingAs($platform)->get(route('developer.tools'))->assertNotFound();
    }

    public function test_language_switch_renders_english(): void
    {
        $this->post(route('locale', 'en'));

        $this->get(route('landing'))->assertOk()->assertSee('Try free for 14 days')->assertSee('dir="ltr"', false);
    }
}
