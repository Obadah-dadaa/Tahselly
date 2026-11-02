<?php

return [

    /*
    | Which site this install is: "live" (tahselly.com, with plans and payment) or "beta"
    | (beta.tahselly.com: free testing only, no prices or payment anywhere, tight limits,
    | its own terms, hidden from search engines). Each install has its own .env and database.
    */
    'edition' => env('APP_EDITION', 'live') === 'beta' ? 'beta' : 'live',

    // Limits for every beta account
    'beta' => [
        'trial_days' => (int) env('BETA_TRIAL_DAYS', 30),
        'max_invoices' => (int) env('BETA_MAX_INVOICES', 50),
        'max_clients' => (int) env('BETA_MAX_CLIENTS', 25),
        'max_users' => (int) env('BETA_MAX_USERS', 2),
        'daily_reminders' => (int) env('BETA_DAILY_REMINDERS', 30),
        'import_rows' => (int) env('BETA_IMPORT_ROWS', 50),
    ],

    // Where the paid site lives, for links from the beta
    'live_url' => env('LIVE_URL', 'https://tahselly.com'),

    /*
    | Crawlers that collect pages to train AI models. They are disallowed in robots.txt and,
    | since some ignore it, answered with 403. AI *search* agents (OAI-SearchBot, ChatGPT-User,
    | Claude-SearchBot, Claude-User, PerplexityBot, Perplexity-User) stay allowed so Tahselly
    | still shows up in AI answers.
    */
    'ai_training_bots' => [
        'GPTBot', 'ClaudeBot', 'anthropic-ai', 'CCBot', 'Google-Extended', 'Applebot-Extended',
        'Bytespider', 'Meta-ExternalAgent', 'Meta-ExternalFetcher', 'FacebookBot', 'Amazonbot',
        'cohere-ai', 'cohere-training-data-crawler', 'Diffbot', 'AI2Bot', 'Ai2Bot-Dolma',
        'Omgilibot', 'Omgili', 'ImagesiftBot', 'Timpibot', 'PanguBot', 'Kangaroo Bot',
        'img2dataset', 'Scrapy', 'VelenPublicWebCrawler', 'Webzio-Extended', 'DeepSeekBot',
        'TikTokSpider', 'SemrushBot-OCOB', 'QualifiedBot', 'Brightbot',
    ],
];
