<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
    }

    public function test_contact_page_shows_the_details_and_emails_messages_to_support(): void
    {
        $this->get(route('contact'))->assertOk()
            ->assertSee('Obadah Dadaa')
            ->assertSee('mailto:obadah@tahselly.com', false)
            ->assertSee('https://wa.me/', false);
        $this->get(route('landing'))->assertSee(route('contact'), false);

        $this->post(route('contact.store'), [
            'name' => 'Sara', 'email' => 'sara@agency.test', 'company' => 'Agency', 'message' => 'Do you support quarterly billing?',
        ])->assertSessionHas('success');

        Mail::assertSent(ContactMessageMail::class, fn ($mail) => $mail->hasTo('obadah@tahselly.com') && $mail->hasReplyTo('sara@agency.test'));
    }

    public function test_bots_and_empty_messages_are_not_sent(): void
    {
        $this->post(route('contact.store'), ['name' => 'Bot', 'email' => 'bot@x.test', 'message' => 'Buy cheap links now!!', 'website' => 'spam.example'])->assertSessionHas('success');
        $this->post(route('contact.store'), ['name' => '', 'email' => 'nope', 'message' => 'hi'])->assertSessionHasErrors(['name', 'email', 'message']);

        Mail::assertNothingSent();
    }
}
