<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderService;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WhatsappQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
    }

    /**
     * A company on the given plan with both channels on and one invoice 7 days overdue.
     *
     * @return array{Company, Invoice, User}
     */
    private function setUpCompany(string $plan = 'agency-basic', array $channels = ['email', 'whatsapp']): array
    {
        $company = Company::factory()->create([
            'plan_id' => Plan::query()->where('code', $plan)->value('id'),
            'status' => 'active',
            'reminder_settings' => ['channels' => $channels],
        ]);
        $client = Client::factory()->create(['company_id' => $company->id, 'phone' => '0501234567', 'email' => 'client@x.test']);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id, 'client_id' => $client->id, 'status' => 'unpaid',
            'issue_date' => today()->subDays(37), 'due_date' => today()->subDays(7), 'amount' => 500, 'paid_amount' => 0,
        ]);

        return [$company, $invoice, User::factory()->create(['company_id' => $company->id, 'role' => Role::Manager])];
    }

    public function test_due_whatsapp_reminders_are_listed_and_sent_with_one_click(): void
    {
        [$company, $invoice, $manager] = $this->setUpCompany();
        $service = app(ReminderService::class);

        // The email engine runs on its own; WhatsApp keeps its own list
        $service->runForCompany($company);
        $queue = $service->whatsappQueue($company);
        $this->assertCount(1, $queue);
        $this->assertSame('overdue', $queue->first()['stage']);

        $this->actingAs($manager)->get(route('reminders.index', ['tab' => 'whatsapp']))->assertOk()->assertSee($invoice->number);

        $response = $this->actingAs($manager)->get(route('reminders.whatsapp', $invoice));
        $this->assertStringStartsWith('https://wa.me/971501234567?text=', $response->headers->get('Location'));
        $this->assertSame(1, Reminder::query()->where('invoice_id', $invoice->id)->where('channel', 'whatsapp')->where('status', 'sent')->count());

        $this->assertCount(0, $service->whatsappQueue($company));
        $this->actingAs($manager)->get(route('reminders.whatsapp', $invoice))->assertSessionHas('error');
    }

    public function test_no_list_without_the_whatsapp_channel_or_plan(): void
    {
        [$company] = $this->setUpCompany('agency-basic', ['email']);
        $this->assertCount(0, app(ReminderService::class)->whatsappQueue($company));

        [$starter, $invoice, $manager] = $this->setUpCompany('freelancer');
        $this->assertCount(0, app(ReminderService::class)->whatsappQueue($starter));
        $this->actingAs($manager)->get(route('reminders.whatsapp', $invoice))->assertForbidden();
    }
}
