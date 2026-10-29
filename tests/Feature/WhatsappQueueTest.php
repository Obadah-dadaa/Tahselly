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
use Illuminate\Support\Facades\Http;
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
        Http::preventStrayRequests();
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

    public function test_send_all_uses_the_click_list_until_the_cloud_api_is_set_up(): void
    {
        [$company, $invoice, $manager] = $this->setUpCompany();

        $this->actingAs($manager)->get(route('reminders.index', ['tab' => 'whatsapp']))
            ->assertSee(__('إرسال الكل'))->assertDontSee(route('reminders.whatsapp.all'));
        $this->actingAs($manager)->post(route('reminders.whatsapp.all'))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_send_all_through_the_cloud_api_personalises_each_message(): void
    {
        config(['services.whatsapp.token' => 'test-token', 'services.whatsapp.phone_number_id' => '123']);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
        [$company, $invoice, $manager] = $this->setUpCompany();

        $this->actingAs($manager)->get(route('reminders.index', ['tab' => 'whatsapp']))->assertSee(route('reminders.whatsapp.all'), false);
        $this->actingAs($manager)->post(route('reminders.whatsapp.all'))->assertSessionHas('success');

        Http::assertSent(function ($request) use ($invoice, $company) {
            $params = collect($request['template']['components'][0]['parameters'])->pluck('text');

            return $request->url() === 'https://graph.facebook.com/v21.0/123/messages'
                && $request['to'] === '971501234567'
                && $request['template']['name'] === 'tahselly_overdue'
                && $params->contains($company->name) && $params->contains($invoice->number) && $params->contains('500.00 '.$invoice->currency);
        });
        $this->assertSame(1, Reminder::query()->where('invoice_id', $invoice->id)->where('channel', 'whatsapp')->where('status', 'sent')->count());
        $this->assertCount(0, app(ReminderService::class)->whatsappQueue($company));
    }

    public function test_a_rejected_message_is_logged_as_failed_and_stays_on_the_list(): void
    {
        config(['services.whatsapp.token' => 'test-token', 'services.whatsapp.phone_number_id' => '123']);
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template not approved']], 400)]);
        [$company, $invoice, $manager] = $this->setUpCompany();

        $this->actingAs($manager)->post(route('reminders.whatsapp.all'))->assertSessionHas('error');

        $this->assertSame('Template not approved', Reminder::query()->where('invoice_id', $invoice->id)->value('error'));
        $this->assertCount(1, app(ReminderService::class)->whatsappQueue($company));
    }

    public function test_shared_number_is_for_paying_companies_only_with_a_daily_cap_and_clean_text(): void
    {
        config(['services.whatsapp.token' => 'test-token', 'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.daily_limit' => 1]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        // Trial accounts keep the click list; they can't send from Tahselly's number
        [$trial, , $trialManager] = $this->setUpCompany();
        $trial->update(['status' => 'trial', 'trial_ends_at' => today()->addDays(5)]);
        $this->actingAs($trialManager)->post(route('reminders.whatsapp.all'))->assertNotFound();
        Http::assertNothingSent();

        // Paying company: the cap stops the second message for today
        [$company, $invoice, $manager] = $this->setUpCompany();
        $second = Invoice::factory()->create([
            'company_id' => $company->id, 'client_id' => $invoice->client_id, 'status' => 'unpaid',
            'issue_date' => today()->subDays(37), 'due_date' => today()->subDays(7), 'amount' => 300, 'paid_amount' => 0,
        ]);
        $company->update(['name' => "Spam\nCo https://evil.example/x"]);
        $this->actingAs($manager)->post(route('reminders.whatsapp.all'))->assertSessionHas('error');

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => collect($request['template']['components'][0]['parameters'])->pluck('text')->contains('Spam Co'));
        $this->assertCount(1, app(ReminderService::class)->whatsappQueue($company));
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
