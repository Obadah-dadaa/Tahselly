<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PlanFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    private function company(string $plan, string $status = 'active'): Company
    {
        return Company::factory()->create([
            'plan_id' => Plan::query()->where('code', $plan)->value('id'),
            'status' => $status,
            'trial_ends_at' => $status === 'trial' ? today()->addDays(10) : null,
        ]);
    }

    private function user(Company $company, Role $role = Role::Owner): User
    {
        return User::factory()->create(['company_id' => $company->id, 'role' => $role]);
    }

    private function invoiceFor(Company $company): Invoice
    {
        $client = Client::factory()->create(['company_id' => $company->id, 'phone' => '+971501234567']);

        return Invoice::factory()->create(['company_id' => $company->id, 'client_id' => $client->id, 'status' => 'unpaid']);
    }

    public function test_plans_include_features_from_their_tier_upwards(): void
    {
        $plans = Plan::query()->ordered()->get()->keyBy('code');

        $this->assertFalse($plans['freelancer']->includes('whatsapp'));
        $this->assertTrue($plans['agency-basic']->includes('whatsapp'));
        $this->assertFalse($plans['agency-basic']->includes('report_exports'));
        $this->assertTrue($plans['services']->includes('report_exports'));
        $this->assertFalse($plans['services']->includes('owner_analytics'));
        $this->assertTrue($plans['agency-pro']->includes('owner_analytics'));
        $this->assertFalse($plans['agency-pro']->includes('activity_log'));
        $this->assertTrue($plans['accounting']->includes('activity_log'));
        $this->assertTrue($plans['freelancer']->includes('excel_import'), 'Ungated features belong to every plan');
    }

    public function test_starter_plan_is_locked_out_of_higher_tier_features(): void
    {
        $company = $this->company('freelancer');
        $owner = $this->user($company);
        $invoice = $this->invoiceFor($company);

        $this->actingAs($owner)->get(route('invoices.whatsapp', $invoice))->assertForbidden()->assertSee(__('ترقية الباقة'));
        $this->actingAs($owner)->get(route('reports.aging.export'))->assertForbidden();
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertForbidden();
        $this->actingAs($owner)->get(route('owner.activity'))->assertForbidden();
        $this->actingAs($owner)->get(route('home'))->assertRedirect(route('dashboard'));

        $this->actingAs($owner)->post(route('reminders.store'), [
            'invoices' => [$invoice->id], 'channel' => 'whatsapp', 'body' => 'Hi', 'when' => 'now',
        ])->assertSessionHas('error');
        $this->assertSame(0, $invoice->reminders()->count());
    }

    public function test_reminder_settings_keep_standard_schedule_and_drop_whatsapp_without_the_features(): void
    {
        $company = $this->company('freelancer');

        $this->actingAs($this->user($company))->put(route('owner.settings.reminders'), [
            'enabled' => 1, 'before_days' => '10', 'after_days' => '2, 4', 'final_after' => 99,
            'channels' => ['email', 'whatsapp'], 'locale' => 'ar',
        ])->assertRedirect();

        $settings = $company->fresh()->reminderSettings();
        $this->assertSame(Company::DEFAULT_REMINDER_SETTINGS['before_days'], $settings['before_days']);
        $this->assertSame(Company::DEFAULT_REMINDER_SETTINGS['after_days'], $settings['after_days']);
        $this->assertSame(['email'], $settings['channels']);
    }

    public function test_professional_plan_can_change_schedule_and_use_whatsapp(): void
    {
        $company = $this->company('agency-pro');
        $owner = $this->user($company);

        $this->actingAs($owner)->put(route('owner.settings.reminders'), [
            'enabled' => 1, 'before_days' => '10', 'after_days' => '2, 4', 'final_after' => 60,
            'channels' => ['email', 'whatsapp'], 'locale' => 'ar',
        ]);
        $settings = $company->fresh()->reminderSettings();
        $this->assertSame([2, 4], $settings['after_days']);
        $this->assertSame(['email', 'whatsapp'], $settings['channels']);

        $this->actingAs($owner)->get(route('invoices.whatsapp', $this->invoiceFor($company)))->assertRedirect();
        $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk();
        $this->actingAs($owner)->get(route('owner.activity'))->assertForbidden();
    }

    public function test_free_trial_opens_every_feature_and_marks_the_ones_above_the_chosen_plan(): void
    {
        $company = $this->company('freelancer', 'trial');
        $owner = $this->user($company);

        $this->actingAs($owner)->get(route('invoices.whatsapp', $this->invoiceFor($company)))->assertRedirect();
        $this->actingAs($owner)->get(route('owner.activity'))->assertOk();
        $this->assertSame('trial', $company->featureState('whatsapp'));
        $this->assertSame('included', $this->company('agency-basic', 'trial')->featureState('whatsapp'));

        $this->actingAs($owner)->withSession(['locale' => 'ar'])->get(route('owner.dashboard'))->assertOk()
            ->assertSee('من باقة الاحترافية');
    }

    public function test_open_invoice_limit_blocks_new_invoices(): void
    {
        $company = $this->company('freelancer');
        $manager = $this->user($company, Role::Manager);
        $client = Client::factory()->create(['company_id' => $company->id]);
        Plan::query()->where('code', 'freelancer')->update(['max_invoices' => 1]);
        $this->invoiceFor($company);

        $this->actingAs($manager)->post(route('invoices.store'), [
            'client_id' => $client->id, 'number' => 'NEW-1', 'issue_date' => '2026-10-01', 'due_date' => '2026-11-01',
            'amount' => 100, 'currency' => 'AED',
        ])->assertSessionHas('error');
        $this->assertFalse(Invoice::query()->where('number', 'NEW-1')->exists());
    }

    public function test_large_imports_need_the_bulk_import_feature(): void
    {
        $company = $this->company('freelancer');
        $manager = $this->user($company, Role::Manager);
        $rows = "رقم الفاتورة,العميل,تاريخ الاستحقاق,المبلغ\n";
        for ($i = 1; $i <= Plan::IMPORT_ROWS_LIMIT + 1; $i++) {
            $rows .= "B-{$i},Client {$i},2026-12-01,100\n";
        }

        $this->actingAs($manager)->post(route('import.store'), ['file' => UploadedFile::fake()->createWithContent('big.csv', $rows)]);

        $this->assertSame(0, Invoice::query()->where('company_id', $company->id)->count());
    }

    public function test_per_client_aging_is_locked_below_enterprise(): void
    {
        $company = $this->company('agency-pro');
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->actingAs($this->user($company, Role::Manager))->withSession(['locale' => 'ar'])->get(route('clients.show', $client))
            ->assertOk()->assertDontSee('أعمار ديون العميل');
    }
}
