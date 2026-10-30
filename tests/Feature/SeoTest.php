<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_home_page_has_search_and_sharing_tags_in_each_language(): void
    {
        $this->get('/?lang=ar')->assertOk()
            ->assertSee('<html lang="ar"', false)
            ->assertSee('نظام متابعة الفواتير وتذكير العملاء بالدفع في الإمارات')
            ->assertSee('<link rel="alternate" hreflang="en" href="'.url('/').'?lang=en">', false)
            ->assertSee('<meta property="og:image" content="'.asset('og-image.png').'">', false)
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('"priceCurrency":"AED"', false)
            ->assertSee('index, follow', false);

        $this->get('/?lang=en')->assertSee('Invoice Follow-up &amp; Payment Reminder Software for UAE Businesses', false);
    }

    public function test_sitemap_lists_public_pages_and_private_pages_are_not_indexed(): void
    {
        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(url('/').'?lang=ar', false)
            ->assertSee(route('contact').'?lang=en', false)
            ->assertDontSee('/owner', false);

        $this->get(route('login'))->assertSee('noindex', false);
        $this->get(route('subscribe.show', ['plan' => 'freelancer']))->assertSee('noindex', false);
    }
}
