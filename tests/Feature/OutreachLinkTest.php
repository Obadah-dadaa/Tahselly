<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutreachLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_personal_link_counts_a_browser_open_and_alerts_once_a_day(): void
    {
        config(['services.telegram.bot_token' => 'token', 'services.telegram.chat_id' => '1']);
        Http::fake();

        $this->get('/go/s1-digital-hub-sol')->assertOk()->assertSee('utm_source=outreach', false)->assertSee('/go/s1-digital-hub-sol/hit', false);

        $browser = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/130.0 Safari/537.36'];
        $this->withHeaders($browser)->get('/go/s1-digital-hub-sol/hit')->assertNoContent();
        $this->withHeaders($browser)->get('/go/s1-digital-hub-sol/hit')->assertNoContent();
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Barracuda link scanner)'])->get('/go/s1-digital-hub-sol/hit');

        $this->assertSame(2, DB::table('link_clicks')->where('ref', 's1-digital-hub-sol')->count());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_contains((string) $request['text'], 'Digital Hub Sol'));

        $this->get('/go/Bad_Ref!')->assertNotFound();
    }

    public function test_the_developer_sees_who_opened_it(): void
    {
        DB::table('link_clicks')->insert(['ref' => 's2-namron-trading', 'created_at' => now()]);
        $developer = User::factory()->create(['company_id' => null, 'role' => \App\Enums\Role::Developer]);

        $this->actingAs($developer)->get(route('developer.dashboard'))->assertOk()->assertSee('Namron Trading');
    }
}
