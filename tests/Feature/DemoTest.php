<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderService;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        config(['services.telegram.bot_token' => 'token', 'services.telegram.chat_id' => '1']);
        Http::fake();
    }

    private function startDemo(): User
    {
        $this->post(route('demo.start'))->assertRedirect(route('home'));

        return auth()->user();
    }

    public function test_the_landing_page_offers_the_demo_whatsapp_and_free_setup(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(route('demo.start'), false)
            ->assertSee('wa.me/971568640530', false)
            ->assertSee('id="setup"', false);
    }

    public function test_a_visitor_gets_a_private_demo_company_with_sample_data(): void
    {
        $user = $this->startDemo();

        $company = $user->company;
        $this->assertTrue($company->isDemo());
        $this->assertTrue($company->hasAccess());
        $this->assertSame(10, $company->clients()->withoutGlobalScopes()->count());
        $this->assertSame(30, $company->invoices()->withoutGlobalScopes()->count());
        $this->assertGreaterThan(0, $company->invoices()->withoutGlobalScopes()->where('status', 'paid')->count());

        $this->get(route('invoices.index'))->assertOk()->assertSee(route('demo.leave'), false);
        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'الديمو'));
    }

    public function test_the_demo_never_sends_real_messages(): void
    {
        Mail::fake();
        $user = $this->startDemo();
        $invoice = Invoice::withoutGlobalScopes()->where('company_id', $user->company_id)->whereIn('status', ['unpaid', 'partial'])->first();

        $this->post(route('invoices.remind', $invoice))->assertSessionHas('success');
        $this->assertSame('sent', Reminder::withoutGlobalScopes()->latest('id')->first()->status);

        app(ReminderService::class)->runAll();
        Mail::assertNothingSent();

        // WhatsApp opens with the message but without the made-up client's number
        $this->assertStringStartsWith('https://wa.me/?text=', app(ReminderService::class)->whatsappLink($invoice));
    }

    public function test_demo_users_cannot_take_account_actions(): void
    {
        $this->startDemo();

        $this->post(route('verification.send'))->assertSessionHas('error');
    }

    public function test_leaving_the_demo_goes_to_sign_up_and_old_demos_are_purged(): void
    {
        $user = $this->startDemo();
        $this->post(route('demo.leave'))->assertRedirect(route('trial.create'))->assertSessionHas('locale', app()->getLocale());
        $this->assertGuest();

        $kept = Company::factory()->create(['status' => 'demo']);
        $user->company->forceFill(['created_at' => now()->subHours(25)])->save();

        $this->artisan('demo:purge')->assertSuccessful();
        $this->assertModelMissing($user->company);
        $this->assertModelMissing($user);
        $this->assertModelExists($kept);
        $this->assertFalse($user->company->hasAccess());
    }
}
