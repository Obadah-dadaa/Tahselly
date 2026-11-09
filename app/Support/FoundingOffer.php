<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Plan;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Founding customers: the first N companies to pay get a lifetime discount on the plan price.
 * The live site counts the seats and charges the discount; the beta (no prices or payment) only
 * shows the offer, reading the live site's status from /founding-offer.json.
 */
class FoundingOffer
{
    /**
     * @return array{open: bool, percent: int, seats: int, left: int}
     */
    public static function status(): array
    {
        if (Edition::isBeta()) {
            return Cache::remember('founding_offer_live', 600, function (): array {
                try {
                    $data = Http::timeout(4)->withUserAgent('Mozilla/5.0 (compatible; TahsellyBeta/1.0)')
                        ->get(rtrim((string) config('tahselly.live_url'), '/').'/founding-offer.json')->throw()->json();

                    return ['open' => (bool) ($data['open'] ?? false), 'percent' => (int) ($data['percent'] ?? 0), 'seats' => (int) ($data['seats'] ?? 0), 'left' => (int) ($data['left'] ?? 0)];
                } catch (Throwable) {
                    return ['open' => false, 'percent' => 0, 'seats' => 0, 'left' => 0];
                }
            });
        }

        $percent = max(0, min(90, (int) SystemSetting::get('founding_offer_percent', 50)));
        $seats = max(0, (int) SystemSetting::get('founding_offer_seats', 20));
        $left = max(0, $seats - Company::query()->whereNotNull('founding_discount')->count());

        return [
            'open' => (bool) SystemSetting::get('founding_offer_enabled', true) && $percent > 0 && $left > 0,
            'percent' => $percent,
            'seats' => $seats,
            'left' => $left,
        ];
    }

    public static function percent(): int
    {
        return self::status()['percent'];
    }

    public static function seats(): int
    {
        return self::status()['seats'];
    }

    public static function seatsLeft(): int
    {
        return self::status()['left'];
    }

    /**
     * Whether new companies can still join the offer.
     */
    public static function open(): bool
    {
        return self::status()['open'];
    }

    /**
     * The discount a company pays with on the live site: its locked one, or the open offer.
     */
    public static function discountFor(?Company $company): int
    {
        if (Edition::isBeta()) {
            return 0;
        }
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
