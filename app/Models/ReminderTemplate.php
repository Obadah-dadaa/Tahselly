<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Not tenant-scoped on purpose: rows with company_id NULL are global defaults.
 * Each template exists per stage, channel and language (ar / en).
 */
#[Fillable(['company_id', 'stage', 'channel', 'locale', 'subject', 'body'])]
class ReminderTemplate extends Model
{
    /** @var list<string> */
    public const PLACEHOLDERS = ['{client_name}', '{invoice_number}', '{amount}', '{balance}', '{currency}', '{due_date}', '{days_overdue}', '{company_name}', '{company_phone}'];

    /** @var list<string> */
    public const LOCALES = ['ar', 'en'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Company override first, then the global default, in the requested language;
     * falls back to Arabic when a language has no template yet.
     */
    public static function resolve(?int $companyId, string $stage, string $channel, string $locale = 'ar'): ?self
    {
        $templateStage = $stage === 'manual' ? 'overdue' : $stage;

        return static::query()
            ->where('stage', $templateStage)
            ->where('channel', $channel)
            ->whereIn('locale', array_unique([$locale, 'ar']))
            ->where(fn ($q) => $q->where('company_id', $companyId)->orWhereNull('company_id'))
            ->orderByRaw('locale = ? DESC', [$locale])
            ->orderByRaw('company_id IS NULL')
            ->first();
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function render(Invoice $invoice): array
    {
        $company = $invoice->company;
        $map = [
            '{client_name}' => $invoice->client->contact_name ?: $invoice->client->name,
            '{invoice_number}' => $invoice->number,
            '{amount}' => number_format((float) $invoice->amount, 2),
            '{balance}' => number_format($invoice->balance(), 2),
            '{currency}' => $invoice->currency,
            '{due_date}' => $invoice->due_date->format('Y-m-d'),
            '{days_overdue}' => (string) $invoice->daysOverdue(),
            '{company_name}' => $company?->name ?? config('app.name'),
            '{company_phone}' => $company?->phone ?? '',
        ];

        return [
            'subject' => strtr((string) $this->subject, $map),
            'body' => strtr($this->body, $map),
        ];
    }
}
