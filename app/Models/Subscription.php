<?php

namespace App\Models;

use App\Support\Referral;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paid (or pending) period of a company on a plan. Platform-level: not tenant-scoped.
 */
#[Fillable(['company_id', 'plan_id', 'cycle', 'amount', 'currency', 'status', 'payment_method', 'gateway_ref', 'paid_at', 'starts_at', 'ends_at', 'notes', 'discount_percent'])]
class Subscription extends Model
{
    /** @var array<string, int> */
    public const CYCLE_MONTHS = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_percent' => 'integer',
            'paid_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeCurrent(Builder $query): void
    {
        $query->where('status', 'active')->where('ends_at', '>', now());
    }

    public function daysLeft(): int
    {
        return $this->ends_at && $this->ends_at->isFuture() ? (int) ceil(now()->diffInDays($this->ends_at)) : 0;
    }

    /**
     * Monthly-equivalent revenue, used for MRR.
     */
    public function monthlyValue(): float
    {
        return round((float) $this->amount / self::CYCLE_MONTHS[$this->cycle], 2);
    }

    /**
     * Mark paid and start the period. Renewals start when the current period ends.
     */
    public function activate(?string $gatewayRef = null): void
    {
        $current = static::query()->current()->with('plan')->where('company_id', $this->company_id)->whereKeyNot($this->id)->latest('ends_at')->first();
        $this->loadMissing('plan');

        if ($current && $this->plan->isHigherThan($current->plan)) {
            // Upgrade: starts now; the days left on the old plan become extra days at the new plan's rate
            $start = now();
            $creditDays = (int) floor(now()->diffInDays($current->ends_at) * $current->monthlyValue() / max(1, $this->monthlyValue()));
            $current->forceFill(['status' => 'expired', 'ends_at' => now()])->save();
        } else {
            // Renewal or same plan: the new period follows the current one
            $start = $current?->ends_at ?? now();
            $creditDays = 0;
        }

        $this->forceFill([
            'status' => 'active',
            'gateway_ref' => $gatewayRef ?? $this->gateway_ref,
            'paid_at' => now(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMonths(self::CYCLE_MONTHS[$this->cycle])->addDays($creditDays),
        ])->save();

        $company = $this->company;
        // Free days earned from referrals while the company had no running period
        if ($company->referral_credit_days > 0) {
            $this->forceFill(['ends_at' => $this->ends_at->copy()->addDays($company->referral_credit_days)])->save();
        }

        $company->forceFill([
            'plan_id' => $this->plan_id,
            'status' => 'active',
            'trial_ends_at' => null,
            'referral_credit_days' => 0,
            // A founding discount paid once stays for every renewal
            'founding_discount' => $company->founding_discount ?: ($this->discount_percent ?: null),
        ])->save();
        $company->users()->update(['is_active' => true]);

        Referral::rewardFor($this);
    }
}
