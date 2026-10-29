<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'category', 'name', 'name_en', 'price', 'price_yearly', 'max_users', 'max_invoices', 'features', 'features_en', 'is_active', 'is_featured', 'sort'])]
class Plan extends Model
{
    /** @var list<string> */
    public const CATEGORIES = ['freelancer', 'agency', 'services', 'accounting'];

    /**
     * Gated features and the cheapest plan (by code) that includes each one.
     * Everything not listed here is part of every plan.
     *
     * @var array<string, string>
     */
    public const FEATURES = [
        'whatsapp' => 'agency-basic',
        'custom_templates' => 'agency-basic',
        'team_permissions' => 'services',
        'report_exports' => 'services',
        'owner_analytics' => 'agency-pro',
        'flexible_schedule' => 'agency-pro',
        'bulk_import' => 'accounting',
        'client_aging' => 'accounting',
        'activity_log' => 'accounting',
    ];

    /** Rows allowed in one Excel import without the bulk_import feature. */
    public const IMPORT_ROWS_LIMIT = 500;

    /** Quarterly billing discount. */
    public const QUARTERLY_DISCOUNT = 0.05;

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'features_en' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
            'price_yearly' => 'decimal:2',
        ];
    }

    /**
     * Cheapest first; plans at the same price go from smaller to bigger limits.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('price')->orderBy('max_users')->orderBy('sort');
    }

    /**
     * The cheapest active-or-not plan that includes a feature, or null when that plan no longer exists.
     */
    public static function minimumFor(string $feature): ?self
    {
        $code = self::FEATURES[$feature] ?? null;

        return $code ? once(fn () => self::query()->where('code', $code)->first()) : null;
    }

    public function includes(string $feature): bool
    {
        if (! array_key_exists($feature, self::FEATURES)) {
            return true;
        }
        $minimum = self::minimumFor($feature);
        if (! $minimum) {
            return true;
        }

        // Plans rank by price, then by size, the same order the pricing page shows
        return [(float) $this->price, $this->max_users] >= [(float) $minimum->price, $minimum->max_users];
    }

    /**
     * Whether this plan sits above another on the pricing ladder (price, then size).
     */
    public function isHigherThan(self $other): bool
    {
        return [(float) $this->price, $this->max_users] > [(float) $other->price, $other->max_users];
    }

    public static function featureLabel(string $feature): string
    {
        return __([
            'whatsapp' => 'تذكيرات واتساب',
            'custom_templates' => 'قوالب رسائل مخصصة',
            'team_permissions' => 'إدارة الفريق والصلاحيات',
            'report_exports' => 'تصدير التقارير إلى Excel',
            'owner_analytics' => 'لوحة المالك والتحليلات',
            'flexible_schedule' => 'جدول تذكيرات مرن',
            'bulk_import' => 'استيراد دفعات كبيرة من Excel',
            'client_aging' => 'تقارير أعمار ديون لكل عميل',
            'activity_log' => 'سجل نشاط كامل',
        ][$feature] ?? $feature);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function priceFor(string $cycle): float
    {
        $monthly = (float) $this->price;

        return match ($cycle) {
            'quarterly' => round($monthly * 3 * (1 - self::QUARTERLY_DISCOUNT)),
            'yearly' => (float) ($this->price_yearly ?: $monthly * 10),
            default => $monthly,
        };
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'en' && $this->name_en ? $this->name_en : $this->name;
    }

    /**
     * @return list<string>
     */
    public function localizedFeatures(): array
    {
        return (app()->getLocale() === 'en' && $this->features_en ? $this->features_en : $this->features) ?? [];
    }

    /**
     * Who the plan is for, shown under its name on the pricing cards.
     */
    public function audience(): string
    {
        return __([
            'freelancer' => 'للمستقلين والأعمال الناشئة',
            'agency-basic' => 'للفرق الصغيرة والوكالات',
            'services' => 'لشركات الخدمات والصيانة',
            'agency-pro' => 'للشركات النامية التي تريد رؤية كاملة',
            'accounting' => 'لمكاتب المحاسبة والشركات الكبيرة',
        ][$this->code] ?? '');
    }

    public static function categoryLabel(string $category): string
    {
        return __([
            'freelancer' => 'المستقلون',
            'agency' => 'وكالات التسويق',
            'services' => 'شركات الخدمات',
            'accounting' => 'مكاتب المحاسبة',
        ][$category] ?? $category);
    }

    public static function cycleLabel(string $cycle): string
    {
        return __(['monthly' => 'شهري', 'quarterly' => 'ربع سنوي', 'yearly' => 'سنوي'][$cycle] ?? $cycle);
    }
}
