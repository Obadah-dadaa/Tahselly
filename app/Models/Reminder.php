<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'invoice_id', 'client_id', 'user_id', 'channel', 'stage', 'day_offset', 'status', 'scheduled_for', 'automatic', 'recipient', 'subject', 'body', 'error'])]
class Reminder extends Model
{
    use BelongsToCompany;

    /** @var array<string, string> */
    public const STAGE_LABELS = [
        'before_due' => 'قبل الاستحقاق',
        'on_due' => 'يوم الاستحقاق',
        'overdue' => 'متأخرة',
        'final' => 'إنذار نهائي',
        'manual' => 'يدوي',
    ];

    protected function casts(): array
    {
        return ['automatic' => 'boolean', 'scheduled_for' => 'datetime'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stageLabel(): string
    {
        return __(self::STAGE_LABELS[$this->stage] ?? $this->stage);
    }
}
