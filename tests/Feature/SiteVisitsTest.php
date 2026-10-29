<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteVisitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_a_visitor_is_counted_once_a_day_without_storing_the_ip(): void
    {
        $browser = ['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0) Chrome/130'];
        $this->withHeaders($browser)->get('/')->assertOk();
        $this->withHeaders($browser)->get('/terms')->assertOk();
        $this->withHeaders($browser)->get('/', ['REMOTE_ADDR' => '10.0.0.9']);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->withHeaders($browser)->get('/');

        $this->assertSame(2, DB::table('site_visits')->count());
        $this->assertSame(0, DB::table('site_visits')->where('visitor', 'like', '%10.0.0.9%')->count());
    }

    public function test_bots_and_signed_in_users_are_not_counted(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])->get('/');
        $this->actingAs(User::factory()->create(['role' => Role::Developer, 'company_id' => null]))->get('/terms');

        $this->assertSame(0, DB::table('site_visits')->count());
    }

    public function test_developer_dashboard_shows_visitors_trials_and_paying_subscribers(): void
    {
        $this->withHeaders(['User-Agent' => 'Mozilla/5.0 Chrome/130'])->get('/');
        Company::factory()->create(['status' => 'trial', 'trial_ends_at' => today()->addDays(2)]);

        $this->actingAs(User::factory()->create(['role' => Role::Developer, 'company_id' => null]))
            ->get(route('developer.dashboard'))
            ->assertOk()
            ->assertSee(__('زوار الموقع اليوم'))
            ->assertSee(__('في التجربة المجانية الآن'))
            ->assertSee(__('مشتركون يدفعون'))
            ->assertSee(__(':count تنتهي تجربتهم خلال 3 أيام', ['count' => 1]));
    }
}
