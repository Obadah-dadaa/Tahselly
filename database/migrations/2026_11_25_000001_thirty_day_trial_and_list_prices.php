<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Owner decision 2026-11-25: a 30-day free trial, and list prices again (the founding discount is switched off).
 * Companies that already locked the founding discount keep it.
 */
return new class extends Migration
{
    public function up(): void
    {
        SystemSetting::query()->where('key', 'default_trial_days')->update(['value' => '30']);
        SystemSetting::query()->where('key', 'founding_offer_enabled')->update(['value' => '0']);
        SystemSetting::flush();
    }

    public function down(): void
    {
        SystemSetting::query()->where('key', 'default_trial_days')->update(['value' => '14']);
        SystemSetting::query()->where('key', 'founding_offer_enabled')->update(['value' => '1']);
        SystemSetting::flush();
    }
};
