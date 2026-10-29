<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    private function developer(Role $role = Role::Developer): User
    {
        return User::factory()->create(['role' => $role, 'company_id' => null]);
    }

    private function paidCompany(): array
    {
        $plan = Plan::query()->where('code', 'agency-pro')->firstOrFail();
        $company = Company::factory()->create(['plan_id' => $plan->id, 'status' => 'active']);
        $subscription = Subscription::create([
            'company_id' => $company->id, 'plan_id' => $plan->id, 'cycle' => 'monthly', 'amount' => 199,
            'status' => 'active', 'payment_method' => 'manual', 'starts_at' => now(), 'ends_at' => now()->addMonth(),
        ]);

        return [$company, $subscription, User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner])];
    }

    public function test_platform_pages_are_a_404_for_guests_and_customers(): void
    {
        $this->get('/developer')->assertNotFound();
        $this->get('/developer/settings')->assertNotFound();

        [, , $owner] = $this->paidCompany();
        $this->actingAs($owner)->get('/developer')->assertNotFound();

        $this->actingAs($this->developer(Role::Platform))->get('/developer')->assertOk();
        $this->actingAs($this->developer(Role::Platform))->get('/developer/settings')->assertNotFound();
        $this->actingAs($this->developer())->get('/developer/settings')->assertOk();
    }

    public function test_there_is_no_way_to_log_in_as_a_customer(): void
    {
        [$company] = $this->paidCompany();

        $this->actingAs($this->developer())->post('/developer/companies/'.$company->id.'/impersonate')->assertNotFound();
        $this->actingAs($this->developer())->get(route('developer.companies.show', $company))->assertOk()->assertDontSee('impersonate');
    }

    public function test_cancelling_a_subscription_ends_the_companys_access(): void
    {
        [$company, $subscription, $owner] = $this->paidCompany();

        $this->actingAs($this->developer())->patch(route('developer.subscriptions.update', $subscription), ['action' => 'cancel', 'reason' => 'Requested'])
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSame('expired', $company->fresh()->status);
        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('owner.billing'));
    }

    public function test_developer_can_deactivate_and_delete_customer_users(): void
    {
        [, , $owner] = $this->paidCompany();
        $developer = $this->developer();

        $this->actingAs($developer)->patch(route('developer.users.toggle', $owner))->assertSessionHas('success');
        $this->assertFalse($owner->fresh()->is_active);

        $this->actingAs($developer)->delete(route('developer.users.destroy', $owner))->assertSessionHas('success');
        $this->assertNull($owner->fresh());

        $this->actingAs($developer)->delete(route('developer.users.destroy', $this->developer(Role::Platform)))->assertNotFound();
        $this->actingAs($this->developer(Role::Platform))->patch(route('developer.users.toggle', $developer))->assertNotFound();
    }

    public function test_developer_can_suspend_and_permanently_delete_a_company(): void
    {
        [$company, , $owner] = $this->paidCompany();
        $client = Client::factory()->create(['company_id' => $company->id]);
        Invoice::factory()->create(['company_id' => $company->id, 'client_id' => $client->id]);
        $developer = $this->developer();

        $this->actingAs($developer)->patch(route('developer.companies.toggle', $company));
        $this->assertSame('suspended', $company->fresh()->status);
        $this->actingAs($developer)->patch(route('developer.companies.toggle', $company));
        $this->assertSame('active', $company->fresh()->status);

        $this->actingAs($developer)->delete(route('developer.companies.destroy', $company), ['confirm' => 'wrong name'])->assertSessionHasErrors('confirm');
        $this->assertNotNull($company->fresh());

        $this->actingAs($developer)->delete(route('developer.companies.destroy', $company), ['confirm' => $company->name])
            ->assertRedirect(route('developer.companies.index'));
        $this->assertNull($company->fresh());
        $this->assertNull($owner->fresh());
        $this->assertSame(0, Invoice::withoutGlobalScopes()->where('company_id', $company->id)->count());
    }

    public function test_trial_owner_goes_straight_to_checkout_for_the_plan_they_picked(): void
    {
        $plan = Plan::query()->where('code', 'services')->firstOrFail();
        $company = Company::factory()->create(['plan_id' => $plan->id, 'status' => 'trial', 'trial_ends_at' => today()->addDays(9)]);
        $owner = User::factory()->create(['company_id' => $company->id, 'role' => Role::Owner]);

        $checkout = route('subscribe.show', ['plan' => 'services', 'cycle' => 'yearly']);
        $this->actingAs($owner)->get(route('dashboard'))->assertOk()->assertSee($checkout, false);
        $this->actingAs($owner)->get(route('owner.billing'))->assertOk()->assertSee($checkout, false);
    }
}
