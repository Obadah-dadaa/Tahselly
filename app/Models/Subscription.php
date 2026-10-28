<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A paid (or pending) period of a company on a plan. Platform-level: not tenant-scoped.
 */
#[Fillable(['company_id', 'plan_id', 'cycle', 'amount', 'currency', 'status', 'payment_method', 'gateway_ref', 'paid_at', 'starts_at', 'ends_at', 'notes'])]
class Subscription extends Model
{
    /** @var array<string, int> */
    public const CYCLE_MONTHS = ['monthly' => 1, 'quarterly' => 3, 'yearly' => 12];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
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
        $current = static::query()->current()->where('company_id', $this->company_id)->whereKeyNot($this->id)->latest('ends_at')->first();
        $start = $current?->ends_at ?? now();

        $this->forceFill([
            'status' => 'active',
            'gateway_ref' => $gatewayRef ?? $this->gateway_ref,
            'paid_at' => now(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMonths(self::CYCLE_MONTHS[$this->cycle]),
        ])->save();

        $this->company->forceFill([
            'plan_id' => $this->plan_id,
            'status' => 'active',
            'trial_ends_at' => null,
        ])->save();
        $this->company->users()->update(['is_active' => true]);
    }
}
