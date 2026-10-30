<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\InterestRequest;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\AccountNotice;
use App\Services\ReminderService;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BetaEditionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
        config(['tahselly.edition' => 'beta']);
        $this->withSession(['locale' => 'ar']);
    }

    private function betaOwner(array $company = []): User
    {
        $company = Company::factory()->create($company + ['plan_id' => null, 'status' => 'trial', 'trial_ends_at' => today()->addDays(20)]);

        return User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner, 'email_verified_at' => now()]);
    }

    public function test_the_beta_shows_no_prices_plans_or_payment_and_is_hidden_from_search(): void
    {
        $this->get('/?lang=ar')->assertOk()
            ->assertSee('BETA')
            ->assertSee('id="interest"', false)
            ->assertSee(route('interest.store'), false)
            ->assertDontSee('id="pricing"', false)
            ->assertDontSee('درهم / شهرياً')
            ->assertDontSee('"priceCurrency"', false)
            ->assertSee('noindex', false);

        $this->get('/subscribe/freelancer')->assertNotFound();
        $this->get('/sitemap.xml')->assertNotFound();
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
        $this->get('/terms?lang=ar')->assertOk()->assertSee('شروط استخدام النسخة التجريبية')->assertSee('50 فاتورة');
        $this->get('/start?lang=ar')->assertOk()->assertSee('نسخة تجريبية مجانية بالكامل')->assertDontSee('name="plan"', false);
    }

    public function test_sign_up_gets_the_beta_period_and_the_welcome_page_has_no_plans(): void
    {
        $this->post(route('trial.store'), [
            'company_name' => 'Beta Co', 'name' => 'Beta Owner', 'email' => 'beta@co.test', 'phone' => '0501234567',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ])->assertRedirect(route('home'));

        $owner = User::query()->where('email', 'beta@co.test')->firstOrFail();
        $this->assertTrue($owner->company->trial_ends_at->isSameDay(today()->addDays(30)));
        $owner->markEmailAsVerified();

        $this->actingAs($owner->fresh())->get(route('owner.plan'))->assertOk()
            ->assertSee('حدود النسخة التجريبية', false)->assertDontSee('درهم');
        $this->get(route('owner.billing'))->assertOk()->assertSee(route('interest.store'), false)->assertDontSee('درهم');
        $this->post(route('owner.plan.choose'), ['plan' => 'freelancer'])->assertNotFound();
    }

    public function test_beta_limits_apply_to_invoices_clients_team_and_daily_reminders(): void
    {
        config(['tahselly.beta.max_invoices' => 1, 'tahselly.beta.max_clients' => 1, 'tahselly.beta.max_users' => 1, 'tahselly.beta.daily_reminders' => 1]);
        $owner = $this->betaOwner();
        $client = Client::factory()->create(['company_id' => $owner->company_id, 'email' => 'c@x.test']);
        $invoice = Invoice::factory()->create(['client_id' => $client->id, 'due_date' => today()->subDays(7), 'issue_date' => today()->subDays(37)]);
        $this->actingAs($owner);

        $this->post(route('clients.store'), ['name' => 'Second', 'email' => 'second@x.test'])->assertSessionHas('error');
        $this->assertSame(1, Client::query()->count());

        $this->post(route('invoices.store'), [
            'client_id' => $client->id, 'number' => 'INV-2', 'issue_date' => today()->toDateString(), 'due_date' => today()->addDays(30)->toDateString(),
            'amount' => 100, 'currency' => 'AED',
        ])->assertSessionHas('error');
        $this->assertSame(1, Invoice::query()->count());

        $this->post(route('owner.team.store'), ['name' => 'M', 'email' => 'm@x.test', 'role' => 'manager', 'password' => 'secret123'])->assertSessionHas('error');

        $service = app(ReminderService::class);
        $this->assertSame('sent', $service->send($invoice, 'manual', 'email', null, false, $owner)->status);
        $second = $service->send($invoice, 'manual', 'email', null, false, $owner);
        $this->assertSame('failed', $second->status);
        $this->assertStringContainsString('1', $second->error);
    }

    public function test_interest_request_reaches_staff_and_activation_extends_access(): void
    {
        Notification::fake();
        $staff = User::factory()->create(['company_id' => null, 'role' => Role::Developer]);
        $owner = $this->betaOwner(['trial_ends_at' => today()->subDay()]);

        // Expired beta account lands on the request page
        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('owner.billing'));
        $this->get(route('owner.billing'))->assertOk()->assertSee('انتهت فترة النسخة التجريبية');

        $this->post(route('interest.store'), ['name' => 'Owner', 'email' => 'o@co.test', 'phone' => '0501112222', 'message' => '200 invoices a month'])
            ->assertSessionHas('success');
        $interest = InterestRequest::query()->firstOrFail();
        $this->assertSame($owner->company_id, $interest->company_id);
        Notification::assertSentTo($staff, AccountNotice::class, fn ($n) => $n->kind === 'interest_requested');

        $this->actingAs($staff)->get(route('developer.interest.index'))->assertOk()->assertSee('200 invoices a month');
        $this->post(route('developer.interest.extend', $interest), ['days' => 90])->assertSessionHas('success');

        $company = $owner->company->fresh();
        $this->assertTrue($company->trial_ends_at->isSameDay(today()->addDays(90)));
        $this->assertTrue($company->hasAccess());
        $this->assertSame('handled', $interest->fresh()->status);
        Notification::assertSentTo($owner, AccountNotice::class, fn ($n) => $n->kind === 'access_extended');
    }

    public function test_the_live_site_is_unchanged(): void
    {
        config(['tahselly.edition' => 'live']);
        $this->get('/?lang=ar')->assertOk()->assertSee('id="pricing"', false)->assertDontSee('id="interest"', false);
        $this->get('/subscribe/freelancer')->assertOk();
        $this->get('/robots.txt')->assertSee('Sitemap:');
    }
}
