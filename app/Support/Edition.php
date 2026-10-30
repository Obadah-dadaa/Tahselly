<?php

namespace App\Support;

/**
 * Live site or beta site (APP_EDITION in .env). The beta shows no prices, plans or payment,
 * gives every account the same small limits, and replaces "subscribe" with a request form.
 */
class Edition
{
    public static function isBeta(): bool
    {
        return config('tahselly.edition') === 'beta';
    }

    /**
     * A beta limit (trial_days, max_invoices, max_clients, max_users, daily_reminders, import_rows).
     */
    public static function limit(string $key): int
    {
        return (int) config('tahselly.beta.'.$key);
    }
}
