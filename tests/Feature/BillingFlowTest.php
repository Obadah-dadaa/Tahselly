<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AccountNotice;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        config(['services.stripe.secret' => null, 'services.payment_sandbox' => false]);
    }

    private function subscribed(string $plan = 'services', string $cycle = 'monthly'): array
    {
        $plan = Plan::query()->where('code', $plan)->firstOrFail();
        $company = Company::factory()->create(['plan_id' => $plan->id, 'status' => 'active']);
        $sub = Subscription::create([
            'company_id' => $company->id, 'plan_id' => $plan->id, 'cycle' => $cycle, 'amount' => $plan->priceFor($cycle),
            'status' => 'active', 'payment_method' => 'manual', 'starts_at' => now(), 'ends_at' => now()->addMonths(Subscription::CYCLE_MONTHS[$cycle]),
        ]);

        return [$company, $sub, User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner])];
    }

    public function test_language_choice_survives_signing_out(): void
    {
        $this->get('/terms')->assertSee('lang="en"', false);
        $this->post(route('locale', 'ar'))->assertCookie('locale', 'ar');

        // A new session (e.g. after signing out) still remembers the choice through the cookie
        $this->flushSession();
        $this->withCookie('locale', 'ar')->get('/terms')->assertSee('lang="ar"', false);
    }

    public function test_subscribers_cannot_buy_a_lower_plan_or_a_shorter_period(): void
    {
        [, , $owner] = $this->subscribed('services', 'quarterly');

        $this->actingAs($owner)->get(route('subscribe.show', 'freelancer'))->assertRedirect(route('owner.billing'));
        $this->actingAs($owner)->get(route('subscribe.show', ['plan' => 'services', 'intent' => 'upgrade']))
            ->assertOk()->assertSee('value="monthly"', false)->assertSee('value="yearly" x-model="cycle" class="sr-only"', false);
        $this->actingAs($owner)->post(route('subscribe.store', 'services'), ['cycle' => 'monthly', 'payment_method' => 'bank_transfer'])
            ->assertSessionHasErrors('cycle');
        $this->actingAs($owner)->post(route('subscribe.store', 'services'), ['cycle' => 'quarterly', 'payment_method' => 'bank_transfer'])
            ->assertRedirect();
    }

    public function test_an_upgrade_starts_now_and_carries_the_unused_days(): void
    {
        [$company, $old] = $this->subscribed('services', 'yearly');
        $pro = Plan::query()->where('code', 'agency-pro')->firstOrFail();
        $new = Subscription::create(['company_id' => $company->id, 'plan_id' => $pro->id, 'cycle' => 'monthly', 'amount' => 199, 'status' => 'pending', 'payment_method' => 'manual']);

        $new->activate('test');

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertTrue($new->fresh()->starts_at->isToday());
        $this->assertGreaterThan(now()->addMonth()->addDays(200), $new->fresh()->ends_at);
        $this->assertSame($pro->id, $company->fresh()->plan_id);
    }

    public function test_staff_hear_about_requests_and_owners_hear_about_approvals(): void
    {
        Notification::fake();
        [, , $owner] = $this->subscribed('services');
        $developer = User::factory()->create(['role' => Role::Developer, 'company_id' => null]);

        $this->actingAs($owner)->post(route('subscribe.store', 'services'), ['cycle' => 'monthly', 'payment_method' => 'bank_transfer']);
        Notification::assertSentTo($developer, AccountNotice::class, fn ($n) => $n->kind === 'subscription_requested');

        $pending = Subscription::query()->where('status', 'pending')->firstOrFail();
        $this->actingAs($developer)->post(route('developer.subscriptions.approve', $pending));
        Notification::assertSentTo($owner, AccountNotice::class, fn ($n) => $n->kind === 'subscription_active');
    }

    public function test_bank_transfer_page_moves_on_after_approval(): void
    {
        $this->post(route('subscribe.store', 'freelancer'), [
            'cycle' => 'monthly', 'payment_method' => 'bank_transfer', 'company_name' => 'New Co', 'name' => 'Owner',
            'email' => 'new@co.test', 'phone' => '0501234567', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1', 'terms' => 1,
        ]);
        $sub = Subscription::query()->latest('id')->firstOrFail();

        $this->getJson(route('subscribe.status', $sub))->assertJson(['status' => 'pending']);
        $this->get(route('subscribe.continue', $sub))->assertRedirect(route('subscribe.pending', $sub));

        $sub->activate('bank-test');
        $this->getJson(route('subscribe.status', $sub))->assertJson(['status' => 'active']);
        $this->get(route('subscribe.continue', $sub))->assertRedirect(route('home'));
        $this->assertAuthenticatedAs(User::query()->where('email', 'new@co.test')->first());
    }

    public function test_owner_activity_log_shows_team_work_only(): void
    {
        [$company, , $owner] = $this->subscribed('accounting');
        ActivityLog::query()->insert([
            ['company_id' => $company->id, 'user_id' => $owner->id, 'action' => 'invoice.created', 'description' => 'Created INV-1', 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company->id, 'user_id' => $owner->id, 'action' => 'subscription.started', 'description' => 'Plan request XYZ', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($owner)->get(route('owner.activity'))->assertOk()->assertSee('Created INV-1')->assertDontSee('Plan request XYZ');
    }
}
