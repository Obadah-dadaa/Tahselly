<?php

namespace Tests\Feature;

use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TelegramAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
        Mail::fake();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        config(['services.telegram.bot_token' => '123:test', 'services.telegram.chat_id' => '-100555']);
    }

    private function sentTexts(): array
    {
        return collect(Http::recorded())->map(fn ($pair) => $pair[0]['text'])->all();
    }

    public function test_sign_ups_contact_messages_and_beta_requests_reach_the_channel(): void
    {
        $this->post(route('trial.store'), [
            'company_name' => 'Gulf Star', 'name' => 'Sara', 'email' => 'sara@gulf.test', 'phone' => '0501234567',
            'password' => 'secret123', 'password_confirmation' => 'secret123', 'terms' => '1',
        ]);
        $this->post(route('contact.store'), ['name' => 'Ali', 'email' => 'ali@x.test', 'message' => 'Do you support quarterly billing?']);
        config(['tahselly.edition' => 'beta']);
        $this->post(route('interest.store'), ['name' => 'Omar', 'email' => 'omar@x.test', 'phone' => '0509998888']);

        $texts = $this->sentTexts();
        $this->assertCount(3, $texts);
        $this->assertStringContainsString('تسجيل جديد', $texts[0]);
        $this->assertStringContainsString('Gulf Star', $texts[0]);
        $this->assertStringContainsString('LIVE', $texts[0]);
        $this->assertStringContainsString('quarterly billing', $texts[1]);
        $this->assertStringContainsString('BETA', $texts[2]);
        $this->assertStringContainsString('Omar', $texts[2]);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/bot123:test/sendMessage') && $request['chat_id'] === '-100555');
    }

    public function test_daily_digest_posts_and_nothing_is_sent_without_a_token(): void
    {
        $this->artisan('telegram:digest')->assertSuccessful();
        $this->assertStringContainsString('ملخص اليوم', $this->sentTexts()[0]);

        config(['services.telegram.bot_token' => null]);
        $this->artisan('telegram:digest')->assertSuccessful();
        $this->assertCount(1, Http::recorded());
    }
}
