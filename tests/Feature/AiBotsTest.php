<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiBotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_disallows_training_bots_but_not_ai_search(): void
    {
        $robots = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString("User-agent: GPTBot\nDisallow: /", $robots);
        $this->assertStringContainsString("User-agent: ClaudeBot\nDisallow: /", $robots);
        $this->assertStringContainsString("User-agent: CCBot\nDisallow: /", $robots);
        $this->assertStringNotContainsString('OAI-SearchBot', $robots);
        $this->assertStringNotContainsString('PerplexityBot', $robots);
        $this->assertStringContainsString("User-agent: *\nAllow: /", $robots);
    }

    public function test_training_bots_get_403_while_people_and_search_engines_get_in(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.2; +https://openai.com/gptbot)')
            ->get('/')->assertForbidden();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Bytespider; spider-feedback@bytedance.com)')
            ->get('/blog')->assertForbidden();
        $this->withHeader('User-Agent', 'CCBot/2.0 (https://commoncrawl.org/faq/)')
            ->get('/robots.txt')->assertOk();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)')
            ->get('/')->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot')
            ->get('/')->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/130.0 Safari/537.36')
            ->get('/')->assertOk();
    }
}
