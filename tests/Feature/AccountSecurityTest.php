<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Mail\AccountLinkMail;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderService;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
    }

    public function test_forgot_password_emails_a_link_and_resets_the_password(): void
    {
        $user = User::factory()->create(['email' => 'forgot@biz.test', 'role' => Role::Owner, 'company_id' => Company::factory()->create()->id]);

        $this->post(route('password.email'), ['email' => 'forgot@biz.test'])->assertSessionHas('success');

        $link = null;
        Mail::assertSent(AccountLinkMail::class, function (AccountLinkMail $mail) use (&$link) {
            $link = $mail->url;

            return $mail->kind === 'reset' && $mail->hasTo('forgot@biz.test');
        });
        preg_match('#reset-password/([^?]+)#', $link, $m);

        $this->post(route('password.update'), [
            'token' => $m[1],
            'email' => 'forgot@biz.test',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-secret-123', $user->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        $this->post(route('password.email'), ['email' => 'nobody@nowhere.test'])->assertSessionHas('success');
        Mail::assertNothingSent();
    }

    public function test_new_trial_must_confirm_email_before_using_the_app(): void
    {
        $this->post(route('trial.store'), [
            'plan' => 'freelancer', 'company_name' => 'Verify Co', 'name' => 'V', 'email' => 'verify@co.test',
            'phone' => '0501234567', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ]);
        $user = User::query()->where('email', 'verify@co.test')->firstOrFail();
        Mail::assertSent(AccountLinkMail::class, fn (AccountLinkMail $mail) => $mail->kind === 'verify');

        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect(route('owner.plan'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_reminder_uses_the_clients_language(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id, 'locale' => 'en']);
        $invoice = Invoice::factory()->overdueBy(5)->create(['client_id' => $client->id]);

        app(ReminderService::class)->send($invoice, 'manual', 'email');

        $reminder = Reminder::query()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertStringContainsString('days overdue', $reminder->subject);
        Mail::assertSent(InvoiceReminderMail::class, fn (InvoiceReminderMail $mail) => $mail->locale === 'en');
    }

    public function test_company_default_language_applies_when_client_has_none(): void
    {
        $company = Company::factory()->create(['reminder_settings' => ['locale' => 'en']]);
        $client = Client::factory()->create(['company_id' => $company->id, 'locale' => null]);

        $this->assertSame('en', $client->messageLocale());
    }
}
