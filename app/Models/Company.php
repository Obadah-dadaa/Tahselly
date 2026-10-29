<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'category', 'email', 'phone', 'trn', 'address', 'currency', 'plan_id', 'status', 'trial_ends_at', 'reminder_settings'])]
class Company extends Model
{
    use HasFactory;

    /**
     * Default automatic reminder schedule (days relative to due date).
     *
     * @var array{enabled: bool, before_days: list<int>, on_due: bool, after_days: list<int>, final_after: int, channels: list<string>}
     */
    public const DEFAULT_REMINDER_SETTINGS = [
        'enabled' => true,
        'before_days' => [3],
        'on_due' => true,
        'after_days' => [7, 14, 30],
        'final_after' => 45,
        'channels' => ['email'],
        'locale' => 'ar',
    ];

    protected function casts(): array
    {
        return ['reminder_settings' => 'array', 'trial_ends_at' => 'date'];
    }

    /**
     * @return array{enabled: bool, before_days: list<int>, on_due: bool, after_days: list<int>, final_after: int, channels: list<string>}
     */
    public function reminderSettings(): array
    {
        return array_merge(self::DEFAULT_REMINDER_SETTINGS, $this->reminder_settings ?? []);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function currentSubscription(): ?Subscription
    {
        return $this->subscriptions()->current()->with('plan')->latest('ends_at')->first();
    }

    public function onTrial(): bool
    {
        return $this->status === 'trial';
    }

    /**
     * Free trials open every feature; paid companies get what their plan includes.
     */
    public function canUse(string $feature): bool
    {
        if ($this->onTrial()) {
            return true;
        }

        return (bool) $this->plan?->includes($feature);
    }

    /**
     * "included" — part of the plan; "trial" — open only because of the free trial; "locked" — needs an upgrade.
     */
    public function featureState(string $feature): string
    {
        if ($this->plan?->includes($feature)) {
            return 'included';
        }

        return $this->onTrial() ? 'trial' : 'locked';
    }

    /**
     * How many more open (unpaid or partly paid) invoices the plan allows; null means no limit.
     */
    public function openInvoiceRoom(): ?int
    {
        if ($this->onTrial() || ! $this->plan) {
            return null;
        }
        $open = $this->invoices()->withoutGlobalScopes()->whereIn('status', ['unpaid', 'partial'])->count();

        return max(0, $this->plan->max_invoices - $open);
    }

    /**
     * Whether the company may use the app right now (paid period or running trial).
     */
    public function hasAccess(): bool
    {
        if ($this->status === 'trial') {
            return ! $this->trial_ends_at || $this->trial_ends_at->endOfDay()->isFuture();
        }

        return $this->status === 'active';
    }
}
