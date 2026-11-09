<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Notifications\AccountNotice;
use App\Services\Telegram;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Referral program (live site only): tahselly.com/r/{code} marks the visitor for 30 days. A company that
 * signs up through it gets a longer trial; when it pays for the first time, the referrer gets free days.
 */
class Referral
{
    public const COOKIE = 'tahselly_ref';

    public static function enabled(): bool
    {
        return ! Edition::isBeta();
    }

    public static function rewardDays(): int
    {
        return max(0, (int) SystemSetting::get('referral_reward_days', 30));
    }

    public static function trialDays(): int
    {
        return max(1, (int) SystemSetting::get('referral_trial_days', 30));
    }

    public static function codeFor(Company $company): string
    {
        if (! $company->referral_code) {
            do {
                // No 0/O or 1/I so the code survives being read out or typed
                $code = Str::upper(substr(str_shuffle(str_repeat('ABCDEFGHJKLMNPQRSTUVWXYZ23456789', 3)), 0, 8));
            } while (Company::query()->where('referral_code', $code)->exists());
            $company->forceFill(['referral_code' => $code])->save();
        }

        return $company->referral_code;
    }

    public static function link(Company $company): string
    {
        return route('referral', self::codeFor($company));
    }

    /**
     * The company whose link brought this visitor, if any.
     */
    public static function referrerFrom(Request $request): ?Company
    {
        if (! self::enabled()) {
            return null;
        }
        $code = $request->cookie(self::COOKIE) ?? $request->session()->get(self::COOKIE);

        return $code ? Company::query()->where('referral_code', $code)->where('status', '!=', 'demo')->first() : null;
    }

    /**
     * Called when a subscription is paid: rewards the referrer once, on the referred company's first payment.
     */
    public static function rewardFor(Subscription $subscription): void
    {
        $company = $subscription->company;
        $referrer = $company->referred_by_id ? Company::query()->find($company->referred_by_id) : null;
        if (! $referrer || $company->referral_rewarded_at || self::rewardDays() === 0) {
            return;
        }
        $company->forceFill(['referral_rewarded_at' => now()])->save();
        $days = self::rewardDays();

        $current = $referrer->currentSubscription();
        if ($current) {
            $current->forceFill(['ends_at' => $current->ends_at->copy()->addDays($days)])->save();
            $until = $current->ends_at;
        } elseif ($referrer->status === 'trial' && $referrer->hasAccess()) {
            $referrer->forceFill(['trial_ends_at' => ($referrer->trial_ends_at ?? today())->copy()->addDays($days)])->save();
            $until = $referrer->trial_ends_at;
        } else {
            // No running period: the days wait for the referrer's next payment
            $referrer->increment('referral_credit_days', $days);
            $until = null;
        }

        ActivityLog::record('referral.rewarded', "مكافأة إحالة: {$days} يوم مجاناً بعد اشتراك {$company->name}", [], $referrer->id);
        AccountNotice::send(AccountNotice::owners($referrer), 'referral_rewarded', [
            'company' => $company->name,
            'days' => (string) $days,
            'date' => $until?->format('Y-m-d') ?? '—',
        ], route('owner.referrals'));
        Telegram::notify('🎁', 'مكافأة إحالة', [
            'الشركة التي أحالت' => $referrer->name,
            'الشركة التي اشتركت' => $company->name,
            'المكافأة' => "{$days} يوم",
        ]);
    }
}
