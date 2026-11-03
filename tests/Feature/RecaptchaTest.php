<?php

namespace Tests\Feature;

use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecaptchaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
        config(['services.recaptcha.site_key' => 'site-key', 'services.recaptcha.secret_key' => 'secret-key']);
    }

    private function google(float $score, string $action = 'contact'): void
    {
        Http::fake(['www.google.com/recaptcha/*' => Http::response(['success' => true, 'score' => $score, 'action' => $action])]);
    }

    private function message(): array
    {
        return ['name' => 'Sara', 'email' => 'sara@example.com', 'message' => 'Hello, I want a demo please.', 'g-recaptcha-response' => 'token'];
    }

    public function test_forms_load_the_invisible_widget_once_keys_are_set(): void
    {
        foreach (['/contact', '/login', '/forgot-password', route('trial.create')] as $url) {
            $this->get($url)->assertOk()->assertSee('recaptcha/api.js?render=site-key', false)->assertSee('data-recaptcha=', false);
        }
    }

    public function test_a_human_score_gets_through(): void
    {
        $this->google(0.9);
        $this->post(route('contact.store'), $this->message())->assertSessionHasNoErrors();
        Http::assertSent(fn ($request) => $request['secret'] === 'secret-key' && $request['response'] === 'token');
    }

    public function test_bots_are_turned_away(): void
    {
        $this->google(0.1);
        $this->from('/contact')->post(route('contact.store'), $this->message())->assertRedirect('/contact')->assertSessionHasErrors('recaptcha');

        $this->google(0.9, 'login'); // token made for another form
        $this->from('/contact')->post(route('contact.store'), $this->message())->assertSessionHasErrors('recaptcha');

        $this->from('/contact')->post(route('contact.store'), ['g-recaptcha-response' => ''] + $this->message())->assertSessionHasErrors('recaptcha');
        $this->from('/login')->post('/login', ['email' => 'a@b.test', 'password' => 'x'])->assertSessionHasErrors('recaptcha');
    }

    public function test_it_stays_off_without_keys(): void
    {
        config(['services.recaptcha.secret_key' => null]);
        Http::fake();
        $this->get('/contact')->assertDontSee('recaptcha/api.js', false);
        $this->post(route('contact.store'), ['g-recaptcha-response' => ''] + $this->message())->assertSessionDoesntHaveErrors('recaptcha');
        Http::assertNothingSent();
    }
}
