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
];
