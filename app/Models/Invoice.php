<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasBetaLimit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'client_id', 'import_batch_id', 'number', 'issue_date', 'due_date', 'amount', 'paid_amount', 'currency', 'status', 'description', 'paid_at', 'last_reminded_at', 'reminders_count', 'reminders_paused', 'synced_at'])]
class Invoice extends Model
{
    use BelongsToCompany, HasBetaLimit, HasFactory;

    public function betaLimitKey(): string
    {
        return 'max_invoices';
    }

    public function betaLimitMessage(int $limit): string
    {
        return __('وصلت لحد النسخة التجريبية (:limit فاتورة). إذا كنت مهتماً بالاشتراك، اطلبه من صفحة «الاشتراك».', ['limit' => $limit]);
    }

    /** @var array<string, string> */
    public const STATUS_LABELS = [
        'unpaid' => 'غير مدفوعة',
        'partial' => 'مدفوعة جزئياً',
        'paid' => 'مدفوعة',
        'cancelled' => 'ملغاة',
        'overdue' => 'متأخرة',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'synced_at' => 'datetime',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'reminders_paused' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('invoices.status', ['unpaid', 'partial']);
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->open()->whereDate('invoices.due_date', '<', today());
    }

    public function balance(): float
    {
        return round((float) $this->amount - (float) $this->paid_amount, 2);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partial'], true) && $this->due_date->lt(today());
    }

    public function daysOverdue(): int
    {
        return $this->isOverdue() ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    /**
     * Status shown to users, distinguishing overdue from merely unpaid.
     */
    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    public function statusLabel(): string
    {
        return __(self::STATUS_LABELS[$this->displayStatus()]);
    }

    /**
     * Recalculate paid amount & status from recorded payments.
     */
    public function refreshPaymentStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }
        $paid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $paid;
        $this->status = match (true) {
            $paid <= 0 => 'unpaid',
            $paid + 0.009 >= (float) $this->amount => 'paid',
            default => 'partial',
        };
        $this->paid_at = $this->status === 'paid' ? ($this->paid_at ?? now()) : null;
        $this->save();
    }
}
