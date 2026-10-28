<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    private function userFor(Company $company, Role $role = Role::Manager): User
    {
        return User::factory()->create(['company_id' => $company->id, 'role' => $role]);
    }

    public function test_manager_cannot_see_other_companies_invoices(): void
    {
        $mine = Company::factory()->create();
        $theirs = Company::factory()->create();
        $foreign = Invoice::factory()->create(['client_id' => Client::factory()->create(['company_id' => $theirs->id])->id]);

        $this->actingAs($this->userFor($mine))
            ->get(route('invoices.show', $foreign))
            ->assertNotFound();
    }

    public function test_roles_are_restricted_to_their_areas(): void
    {
        $company = Company::factory()->create();
        $manager = $this->userFor($company);
        $owner = $this->userFor($company, Role::Owner);

        $this->actingAs($manager)->get(route('dashboard'))->assertOk();
        $this->actingAs($manager)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('developer.dashboard'))->assertForbidden();
    }

    public function test_suspended_company_is_logged_out(): void
    {
        $company = Company::factory()->create(['status' => 'suspended']);

        $this->actingAs($this->userFor($company))
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_recording_full_payment_marks_invoice_paid(): void
    {
        $company = Company::factory()->create();
        $invoice = Invoice::factory()->overdueBy(5)->create([
            'client_id' => Client::factory()->create(['company_id' => $company->id])->id,
            'amount' => 1000,
        ]);

        $this->actingAs($this->userFor($company))
            ->post(route('payments.store', $invoice), ['amount' => 1000, 'paid_on' => today()->toDateString(), 'method' => 'cash'])
            ->assertSessionHasNoErrors();

        $this->assertSame('paid', $invoice->fresh()->status);
    }
}
