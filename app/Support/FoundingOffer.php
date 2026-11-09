<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Plan;
use App\Models\SystemSetting;

/**
 * Founding customers: the first N companies to pay get a lifetime discount (live site only).
 * The discount is fixed on the subscription at checkout and locked on the company when it is paid.
 */
class FoundingOffer
{
    public static function percent(): int
    {
        return max(0, min(90, (int) SystemSetting::get('founding_offer_percent', 50)));
    }

    public static function seatsLeft(): int
    {
        return max(0, (int) SystemSetting::get('founding_offer_seats', 20) - Company::query()->whereNotNull('founding_discount')->count());
    }

    /**
     * Whether new companies can still join the offer.
     */
    public static function open(): bool
    {
        return ! Edition::isBeta() && (bool) SystemSetting::get('founding_offer_enabled', true) && self::percent() > 0 && self::seatsLeft() > 0;
    }

    /**
     * The discount a company pays with: its locked one, or the open offer.
     */
    public static function discountFor(?Company $company): int
    {
        if ($company?->founding_discount) {
            return (int) $company->founding_discount;
        }

        return self::open() && ! $company?->isDemo() ? self::percent() : 0;
    }

    public static function price(Plan $plan, string $cycle, int $discount): float
    {
        // Whole dirhams, so the price on the page is exactly what the card is charged
        return $discount ? round($plan->priceFor($cycle) * (100 - $discount) / 100) : $plan->priceFor($cycle);
    }
}
