<?php

namespace Tests\Feature;

use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\ReminderService;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderEngineTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_overdue_invoice_gets_one_reminder_per_stage(): void
    {
        $invoice = Invoice::factory()->overdueBy(8)->create(['client_id' => $this->client->id]);
        $service = app(ReminderService::class);

        $this->assertSame(['sent' => 1, 'failed' => 0], $service->runForCompany($this->company));
        $this->assertSame(['sent' => 0, 'failed' => 0], $service->runForCompany($this->company));

        Mail::assertSent(InvoiceReminderMail::class, 1);
        $this->assertDatabaseHas('reminders', ['invoice_id' => $invoice->id, 'stage' => 'overdue', 'day_offset' => 7, 'status' => 'sent']);
        $this->assertSame(1, $invoice->fresh()->reminders_count);
    }

    public function test_template_placeholders_are_rendered(): void
    {
        $invoice = Invoice::factory()->dueIn(3)->create(['client_id' => $this->client->id, 'number' => 'INV-777', 'amount' => 1500]);

        app(ReminderService::class)->runForCompany($this->company);

        $reminder = $invoice->reminders()->first();
        $this->assertSame('before_due', $reminder->stage);
        $this->assertStringContainsString('INV-777', $reminder->subject);
        $this->assertStringContainsString('1,500.00', $reminder->body);
        $this->assertStringNotContainsString('{', $reminder->body);
    }

    public function test_paid_invoices_and_disabled_clients_are_skipped(): void
    {
        Invoice::factory()->overdueBy(10)->create(['client_id' => $this->client->id, 'status' => 'paid']);
        $muted = Client::factory()->create(['company_id' => $this->company->id, 'reminders_enabled' => false]);
        Invoice::factory()->overdueBy(10)->create(['client_id' => $muted->id]);

        $result = app(ReminderService::class)->runForCompany($this->company);

        $this->assertSame(0, $result['sent']);
        Mail::assertNothingSent();
    }

    public function test_client_without_email_is_logged_as_failed(): void
    {
        $client = Client::factory()->create(['company_id' => $this->company->id, 'email' => null]);
        Invoice::factory()->overdueBy(0)->create(['client_id' => $client->id]);

        $result = app(ReminderService::class)->runForCompany($this->company);

        $this->assertSame(1, $result['failed']);
        $this->assertDatabaseHas('reminders', ['client_id' => $client->id, 'stage' => 'on_due', 'status' => 'failed']);
    }

    public function test_final_notice_replaces_overdue_stage_after_threshold(): void
    {
        $invoice = Invoice::factory()->overdueBy(50)->create(['client_id' => $this->client->id]);

        app(ReminderService::class)->runForCompany($this->company);

        $this->assertDatabaseHas('reminders', ['invoice_id' => $invoice->id, 'stage' => 'final', 'day_offset' => 45]);
    }
}
