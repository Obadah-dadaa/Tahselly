<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasBetaLimit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'contact_name', 'email', 'phone', 'trn', 'address', 'notes', 'reminders_enabled', 'locale'])]
class Client extends Model
{
    use BelongsToCompany, HasBetaLimit, HasFactory;

    public function betaLimitKey(): string
    {
        return 'max_clients';
    }

    public function betaLimitMessage(int $limit): string
    {
        return __('وصلت لحد النسخة التجريبية (:limit عميل).', ['limit' => $limit]);
    }

    protected function casts(): array
    {
        return ['reminders_enabled' => 'boolean'];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Phone in international format suitable for wa.me links (UAE default).
     */
    /**
     * Language reminders to this client are written in: the client's own choice, else the company default.
     */
    public function messageLocale(): string
    {
        $locale = $this->locale ?: ($this->company?->reminderSettings()['locale'] ?? 'ar');

        return in_array($locale, ReminderTemplate::LOCALES, true) ? $locale : 'ar';
    }

    public function whatsappNumber(): ?string
    {
        if (! $this->phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $this->phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '05')) {
            $digits = '971'.substr($digits, 1);
        }

        return $digits;
    }
}
