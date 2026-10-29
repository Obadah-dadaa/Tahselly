<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\TrialEndingMail;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class FreeTrialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function signup(array $overrides = []): array
    {
        return array_merge([
            'plan' => 'agency-pro',
            'company_name' => 'Trial Agency',
            'name' => 'Trial Owner',
            'email' => 'trial@agency.test',
            'phone' => '0501234567',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'terms' => '1',
        ], $overrides);
    }

    public function test_visitor_starts_a_trial_without_a_card(): void
    {
        $this->get(route('trial.create'))->assertOk()->assertSee('14');

        $this->post(route('trial.store'), $this->signup())->assertRedirect(route('home'));

        $company = Company::query()->where('name', 'Trial Agency')->firstOrFail();
        $this->assertSame('trial', $company->status);
        $this->assertSame(today()->addDays(14)->toDateString(), $company->trial_ends_at->toDateString());
        $this->assertSame(0, $company->subscriptions()->count());
        $this->assertAuthenticatedAs(User::query()->where('email', 'trial@agency.test')->first());
    }

    public function test_trial_requires_accepting_the_paid_after_trial_terms(): void
    {
        $this->post(route('trial.store'), $this->signup(['terms' => '']))->assertSessionHasErrors('terms');
        $this->assertGuest();
    }

    public function test_dashboard_shows_the_trial_countdown(): void
    {
        $company = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => today()->addDays(5)]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner]);

        $this->actingAs($owner)->withSession(['locale' => 'ar'])->get(route('dashboard'))->assertOk()->assertSeeInOrder(['>5<', 'يوم متبقٍ'], false);
    }

    public function test_ended_trial_locks_the_account_until_payment(): void
    {
        $company = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => today()->subDay()]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner]);

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('owner.billing'));

        $this->artisan('subscriptions:expire')->assertSuccessful();
        $this->assertSame('expired', $company->fresh()->status);
    }

    public function test_owners_are_emailed_three_days_before_and_on_the_last_day(): void
    {
        Mail::fake();
        foreach ([3 => 'soon@t.test', 0 => 'today@t.test', 5 => 'later@t.test'] as $days => $email) {
            $company = Company::factory()->create(['status' => 'trial', 'trial_ends_at' => today()->addDays($days)]);
            User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner, 'email' => $email]);
        }

        $this->artisan('trials:notify')->assertSuccessful();

        Mail::assertSent(TrialEndingMail::class, 2);
        Mail::assertSent(TrialEndingMail::class, fn ($mail) => $mail->hasTo('soon@t.test'));
        Mail::assertSent(TrialEndingMail::class, fn ($mail) => $mail->hasTo('today@t.test'));
        Mail::assertNotSent(TrialEndingMail::class, fn ($mail) => $mail->hasTo('later@t.test'));
    }

    public function test_signups_can_be_switched_off(): void
    {
        SystemSetting::query()->where('key', 'allow_signups')->update(['value' => '0']);
        SystemSetting::flush();

        $this->get(route('trial.create'))->assertNotFound();
    }

    public function test_legal_pages_render_in_both_languages(): void
    {
        $this->post(route('locale', 'ar'));
        $this->get(route('legal.terms'))->assertOk()->assertSee('التجربة المجانية');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('45 لسنة 2021');

        $this->post(route('locale', 'en'));
        $this->get(route('legal.terms'))->assertOk()->assertSee('Free trial');
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Decree-Law No. 45 of 2021');
    }

    public function test_trial_owners_land_on_the_trial_welcome_after_confirming_email_and_every_login(): void
    {
        $this->get(route('trial.create'))->assertOk()->assertDontSee('name="plan"', false);

        $this->post(route('trial.store'), $this->signup(['plan' => null]))->assertRedirect(route('home'));
        $owner = User::query()->where('email', 'trial@agency.test')->firstOrFail();
        $this->assertNull($owner->company->plan_id);
        $this->assertSame('included', $owner->company->featureState('activity_log'), 'No plan yet: everything open, no badges');

        // Confirming the email opens the trial welcome page
        $verify = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $owner->id, 'hash' => sha1($owner->email)]);
        $this->actingAs($owner->fresh())->get($verify)->assertRedirect(route('owner.plan'));
        $this->get(route('owner.plan'))->assertOk()->assertSee(route('dashboard'), false);

        // ... and so does every sign-in while the trial runs
        foreach ([1, 2] as $attempt) {
            $this->post(route('logout'));
            $this->post(route('login'), ['email' => 'trial@agency.test', 'password' => 'secret123'])->assertRedirect(route('owner.plan'));
        }
        $this->get(route('home'))->assertRedirect(route('owner.dashboard'));

        $this->post(route('owner.plan.choose'), ['plan' => 'services'])->assertRedirect(route('dashboard'));
        $this->assertSame('services', $owner->company->fresh()->plan->code);
    }

    public function test_a_plan_picked_on_the_pricing_page_is_kept_at_sign_up(): void
    {
        $this->get(route('trial.create', ['plan' => 'agency-pro']))->assertOk()->assertSee('name="plan" value="agency-pro"', false);
        $this->post(route('trial.store'), $this->signup());

        $this->assertSame('agency-pro', User::query()->where('email', 'trial@agency.test')->firstOrFail()->company->plan->code);
    }
}
