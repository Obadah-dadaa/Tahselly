<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\FoundingOffer;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Free reminder generator, referral program and founding-customer offer.
 */
class GrowthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        config(['services.payment_sandbox' => true, 'services.stripe.secret' => null]);
    }

    private function setting(string $key, string $value): void
    {
        SystemSetting::query()->where('key', $key)->update(['value' => $value]);
        SystemSetting::flush();
    }

    private function signup(string $email): array
    {
        return [
            'company_name' => 'New Co '.$email, 'name' => 'Owner', 'email' => $email, 'phone' => '0501234567',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ];
    }

    /** An owner whose company has a paid yearly subscription */
    private function payingOwner(): User
    {
        $company = Company::factory()->create(['status' => 'trial']);
        $plan = Plan::query()->where('code', 'freelancer')->first();
        $company->subscriptions()->create(['plan_id' => $plan->id, 'cycle' => 'yearly', 'amount' => 990, 'status' => 'pending', 'payment_method' => 'manual'])->activate('test');

        return User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner, 'email_verified_at' => now()]);
    }

    public function test_the_reminder_generator_is_public_and_in_the_sitemap(): void
    {
        $this->get(route('tools.reminder'))->assertOk()->assertSee('{client}', false)->assertSee(route('demo.start'), false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/tools/payment-reminder', false);
        $this->get(route('blog.index'))->assertSee(route('tools.reminder'), false);
    }

    public function test_a_referred_company_gets_a_longer_trial_and_pays_the_referrer_in_free_days(): void
    {
        $referrer = $this->payingOwner();
        $endsBefore = $referrer->company->currentSubscription()->ends_at;

        $this->actingAs($referrer)->get(route('owner.referrals'))->assertOk();
        $code = $referrer->company->fresh()->referral_code;
        $this->assertNotNull($code);
        auth()->logout();

        $this->get('/r/'.strtolower($code))->assertRedirect()->assertCookie('tahselly_ref', $code);
        $this->post(route('trial.store'), $this->signup('friend@co.test'))->assertRedirect(route('home'));

        $friend = User::query()->where('email', 'friend@co.test')->first()->company;
        $this->assertSame($referrer->company_id, $friend->referred_by_id);
        $this->assertTrue($friend->trial_ends_at->isSameDay(today()->addDays(30)));

        // The friend's first payment rewards the referrer once
        $plan = Plan::query()->where('code', 'freelancer')->first();
        $friend->subscriptions()->create(['plan_id' => $plan->id, 'cycle' => 'monthly', 'amount' => 99, 'status' => 'pending', 'payment_method' => 'manual'])->activate('pay-1');
        $this->assertTrue($referrer->company->currentSubscription()->ends_at->equalTo($endsBefore->copy()->addDays(30)));
        $this->assertNotNull($friend->fresh()->referral_rewarded_at);
        $this->assertSame(1, $referrer->notifications()->count());

        $friend->subscriptions()->create(['plan_id' => $plan->id, 'cycle' => 'monthly', 'amount' => 99, 'status' => 'pending', 'payment_method' => 'manual'])->activate('pay-2');
        $this->assertTrue($referrer->company->currentSubscription()->ends_at->equalTo($endsBefore->copy()->addDays(30)));
    }

    public function test_a_referrer_without_a_running_period_keeps_the_days_for_the_next_payment(): void
    {
        $referrer = Company::factory()->create(['status' => 'expired', 'referral_code' => 'ABCDEFGH']);
        $friend = Company::factory()->create(['status' => 'trial', 'referred_by_id' => $referrer->id]);
        $plan = Plan::query()->where('code', 'freelancer')->first();

        $friend->subscriptions()->create(['plan_id' => $plan->id, 'cycle' => 'monthly', 'amount' => 99, 'status' => 'pending', 'payment_method' => 'manual'])->activate();
        $this->assertSame(30, $referrer->fresh()->referral_credit_days);

        $own = $referrer->subscriptions()->create(['plan_id' => $plan->id, 'cycle' => 'monthly', 'amount' => 99, 'status' => 'pending', 'payment_method' => 'manual']);
        $own->activate();
        $this->assertTrue($own->fresh()->ends_at->between(now()->addMonth()->addDays(30)->subMinute(), now()->addMonth()->addDays(30)->addMinute()));
        $this->assertSame(0, $referrer->fresh()->referral_credit_days);
    }

    public function test_founding_customers_pay_the_discount_for_life_until_the_seats_run_out(): void
    {
        $this->setting('founding_offer_seats', '1');
        $plan = Plan::query()->where('code', 'freelancer')->first();

        $this->get('/')->assertSee('50%');
        $this->post(route('subscribe.store', 'freelancer'), $this->signup('first@co.test') + ['cycle' => 'yearly', 'payment_method' => 'card']);
        $first = Subscription::query()->latest('id')->first();
        $this->assertEquals($plan->priceFor('yearly') / 2, $first->amount);
        $this->post(route('subscribe.sandbox.pay', $first), ['card_number' => '4242 4242 4242 4242', 'expiry' => '12/28', 'cvc' => '123']);
        $this->assertSame(50, $first->company->fresh()->founding_discount);
        $this->assertSame(0, FoundingOffer::seatsLeft());

        // The founder keeps it on renewal; a newcomer after the last seat pays the list price
        $this->assertSame(50, FoundingOffer::discountFor($first->company->fresh()));
        auth()->logout();
        $this->post(route('subscribe.store', 'freelancer'), $this->signup('late@co.test') + ['cycle' => 'monthly', 'payment_method' => 'card']);
        $this->assertEquals($plan->priceFor('monthly'), Subscription::query()->latest('id')->first()->amount);
        $this->get('/')->assertDontSee(__('عرض المؤسسين: خصم :percent% مدى الحياة', ['percent' => 50]));
    }

    public function test_the_beta_shows_the_live_founding_offer_and_rewards_referrals_when_access_is_granted(): void
    {
        config(['tahselly.edition' => 'beta', 'tahselly.live_url' => 'https://live.test']);
        Http::fake(['live.test/founding-offer.json' => Http::response(['open' => true, 'percent' => 50, 'seats' => 20, 'left' => 17])]);

        $this->get('/')->assertOk()->assertSee('باقي 17 مكاناً')->assertSee('id="free"', false)->assertSee('id="integrations"', false);
        $this->assertSame(0, FoundingOffer::discountFor(null)); // nothing is sold on the beta

        $referrer = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => today()->addDays(5), 'referral_code' => 'ABCDEFGH']);
        $this->get('/r/abcdefgh')->assertCookie('tahselly_ref', 'ABCDEFGH');
        $this->post(route('trial.store'), $this->signup('beta-friend@co.test'))->assertRedirect(route('home'));
        $friend = User::query()->where('email', 'beta-friend@co.test')->first()->company;
        $this->assertSame($referrer->id, $friend->referred_by_id);

        // Granting the friend full access (the beta's "subscription") rewards the referrer
        $interest = \App\Models\InterestRequest::query()->create(['company_id' => $friend->id, 'name' => 'Friend', 'company_name' => $friend->name, 'email' => 'beta-friend@co.test', 'phone' => '+971501234567', 'status' => 'new']);
        $this->actingAs(User::factory()->create(['role' => Role::Platform]))->post(route('developer.interest.extend', $interest), ['days' => 90]);
        $this->assertTrue($referrer->fresh()->trial_ends_at->isSameDay(today()->addDays(35)));
    }
}
