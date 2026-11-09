<?php

use App\Models\SystemSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Referral program: each company's share code, who brought it, and the reward bookkeeping
            $table->string('referral_code', 12)->nullable()->unique();
            $table->foreignId('referred_by_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->timestamp('referral_rewarded_at')->nullable();
            $table->unsignedInteger('referral_credit_days')->default(0);
            // Founding customers: the lifetime discount locked in with the first payment
            $table->unsignedTinyInteger('founding_discount')->nullable();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0);
        });

        foreach ([
            ['founding_offer_enabled', '1', 'bool', 'billing', 'عرض المؤسسين: خصم مدى الحياة لأول الشركات المشتركة'],
            ['founding_offer_percent', '50', 'int', 'billing', 'عرض المؤسسين: نسبة الخصم %'],
            ['founding_offer_seats', '20', 'int', 'billing', 'عرض المؤسسين: عدد الشركات'],
            ['referral_reward_days', '30', 'int', 'billing', 'برنامج الإحالة: أيام مجانية لمن يُحيل شركة تشترك'],
            ['referral_trial_days', '30', 'int', 'billing', 'برنامج الإحالة: أيام التجربة للشركة المُحالة'],
            ['google_tag_id', '', 'string', 'general', 'معرّف Google tag (مثل G-XXXX أو AW-XXXX) — فارغ = بدون تتبّع'],
            ['google_ads_signup_conversion', '', 'string', 'general', 'Google Ads: تحويل التسجيل (AW-XXXX/label)'],
            ['google_ads_demo_conversion', '', 'string', 'general', 'Google Ads: تحويل فتح الديمو (AW-XXXX/label)'],
        ] as [$key, $value, $type, $group, $label]) {
            SystemSetting::query()->firstOrCreate(['key' => $key], compact('value', 'type', 'group', 'label'));
        }
        SystemSetting::flush();
    }

    public function down(): void
    {
        SystemSetting::query()->whereIn('key', ['founding_offer_enabled', 'founding_offer_percent', 'founding_offer_seats', 'referral_reward_days', 'referral_trial_days', 'google_tag_id', 'google_ads_signup_conversion', 'google_ads_demo_conversion'])->delete();
        SystemSetting::flush();

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('discount_percent');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_id');
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referral_rewarded_at', 'referral_credit_days', 'founding_discount']);
        });
    }
};
