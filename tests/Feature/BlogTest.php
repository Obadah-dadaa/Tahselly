<?php

namespace Tests\Feature;

use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformSeeder::class);
    }

    public function test_blog_lists_and_shows_articles_in_both_languages(): void
    {
        $this->get('/blog?lang=en')->assertOk()
            ->assertSee('How to Collect Overdue Invoices in the UAE')
            ->assertSee(route('blog.show', 'payment-reminder-templates'), false);

        $this->get('/blog/collect-overdue-invoices-uae?lang=ar')->assertOk()
            ->assertSee('كيف تحصّل فواتيرك المتأخرة في الإمارات')
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('<link rel="alternate" hreflang="en"', false)
            ->assertSee('<table>', false)
            ->assertSee(route('trial.create'), false);

        $this->get('/blog/no-such-article')->assertNotFound();
        $this->get('/sitemap.xml')->assertSee(route('blog.show', 'accounts-receivable-aging-explained').'?lang=ar', false);
    }

    public function test_every_article_exists_in_arabic_and_english(): void
    {
        $ar = collect(glob(resource_path('content/blog/*.ar.md')))->map(fn ($f) => basename($f, '.ar.md'))->sort()->values();
        $en = collect(glob(resource_path('content/blog/*.en.md')))->map(fn ($f) => basename($f, '.en.md'))->sort()->values();

        $this->assertNotEmpty($ar);
        $this->assertEquals($ar, $en);
    }
}
