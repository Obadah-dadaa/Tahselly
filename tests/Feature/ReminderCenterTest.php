<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\ReminderTemplate;
use App\Models\User;
use App\Services\ReminderService;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderCenterTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $manager;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
        $this->company = Company::factory()->create();
        $this->manager = User::factory()->create(['company_id' => $this->company->id, 'role' => Role::Manager]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_manual_reminder_with_custom_message_is_sent_to_many_invoices(): void
    {
        $invoices = Invoice::factory()->count(2)->overdueBy(5)->create(['client_id' => $this->client->id]);

        $this->actingAs($this->manager)->post(route('reminders.store'), [
            'invoices' => $invoices->pluck('id')->all(),
            'channel' => 'email',
            'subject' => 'Hello {client_name}',
            'body' => 'Invoice {invoice_number} is {days_overdue} days late',
            'when' => 'now',
        ])->assertRedirect(route('reminders.index', ['tab' => 'log']));

        Mail::assertSent(InvoiceReminderMail::class, 2);
        $reminder = Reminder::query()->where('invoice_id', $invoices[0]->id)->first();
        $this->assertSame('Invoice '.$invoices[0]->number.' is 5 days late', $reminder->body);
    }

    public function test_scheduled_reminder_goes_out_when_due_and_skips_paid_invoices(): void
    {
        $open = Invoice::factory()->overdueBy(3)->create(['client_id' => $this->client->id]);
        $paid = Invoice::factory()->overdueBy(3)->create(['client_id' => $this->client->id]);

        $this->actingAs($this->manager)->post(route('reminders.store'), [
            'invoices' => [$open->id, $paid->id],
            'channel' => 'email',
            'body' => 'Reminder {invoice_number}',
            'when' => 'later',
            'scheduled_for' => now()->addHour()->format('Y-m-d H:i'),
        ])->assertRedirect(route('reminders.index', ['tab' => 'scheduled']));

        $this->assertSame(2, Reminder::query()->where('status', 'scheduled')->count());
        Mail::assertNothingSent();

        $paid->update(['status' => 'paid']);
        $this->travel(2)->hours();
        $this->assertSame(1, app(ReminderService::class)->dispatchScheduled());

        Mail::assertSent(InvoiceReminderMail::class, 1);
        $this->assertSame('sent', Reminder::query()->where('invoice_id', $open->id)->value('status'));
        $this->assertSame('cancelled', Reminder::query()->where('invoice_id', $paid->id)->value('status'));
    }

    public function test_paused_invoice_is_left_out_of_automatic_and_upcoming_reminders(): void
    {
        $paused = Invoice::factory()->overdueBy(7)->create(['client_id' => $this->client->id, 'reminders_paused' => true]);
        $active = Invoice::factory()->dueIn(2)->create(['client_id' => $this->client->id]);
        $service = app(ReminderService::class);

        $service->runForCompany($this->company);
        $this->assertDatabaseMissing('reminders', ['invoice_id' => $paused->id]);
        $this->assertDatabaseHas('reminders', ['invoice_id' => $active->id, 'stage' => 'before_due']);

        $upcoming = $service->upcoming($this->company, 14);
        $this->assertFalse($upcoming->contains(fn ($row) => $row['invoice']->is($paused)));
        $this->assertTrue($upcoming->contains(fn ($row) => $row['invoice']->is($active) && $row['stage'] === 'on_due'));
    }

    public function test_unedited_prefilled_message_uses_the_template_matching_each_invoice(): void
    {
        $notDueYet = Invoice::factory()->dueIn(10)->create(['client_id' => $this->client->id]);
        $prefill = ReminderTemplate::resolve($this->company->id, 'overdue', 'email');

        $this->actingAs($this->manager)->post(route('reminders.store'), [
            'invoices' => [$notDueYet->id],
            'channel' => 'email',
            'subject' => $prefill->subject,
            'body' => str_replace("\n", "\r\n", $prefill->body),
            'when' => 'now',
        ])->assertRedirect();

        $reminder = Reminder::query()->where('invoice_id', $notDueYet->id)->firstOrFail();
        $this->assertStringContainsString('تذكير ودّي', $reminder->subject);
        $this->assertStringNotContainsString('متأخرة', $reminder->subject);
    }

    public function test_reminders_center_pages_render(): void
    {
        Invoice::factory()->dueIn(3)->create(['client_id' => $this->client->id]);

        foreach (['upcoming', 'scheduled', 'log'] as $tab) {
            $this->actingAs($this->manager)->get(route('reminders.index', ['tab' => $tab]))->assertOk();
        }
        $this->actingAs($this->manager)->get(route('reminders.create'))->assertOk();
    }
}
