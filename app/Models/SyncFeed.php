<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A company's daily report sync: the private address its accounting system emails the open-invoices report to,
 * who may send it, and how the report's columns map to ours when they are not recognised on their own.
 */
#[Fillable(['company_id', 'token', 'senders', 'mapping', 'enabled', 'last_received_at', 'stale_alerted_at'])]
class SyncFeed extends Model
{
    protected function casts(): array
    {
        return ['mapping' => 'array', 'enabled' => 'boolean', 'last_received_at' => 'datetime', 'stale_alerted_at' => 'datetime'];
    }

    public static function for(Company $company): self
    {
        return self::query()->firstOrCreate(['company_id' => $company->id], [
            'token' => self::newToken(),
            // Start with the owner's own address so a first test from their mailbox works
            'senders' => $company->users()->where('role', 'owner')->value('email'),
        ]);
    }

    public static function newToken(): string
    {
        do {
            $token = Str::lower(Str::random(12));
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }

    public function address(): string
    {
        return str_replace('{token}', $this->token, (string) config('services.sync_inbox.address'));
    }

    /**
     * The token in any recipient address of an inbound message.
     */
    public static function tokenIn(string $recipient): ?string
    {
        [$before, $after] = explode('{token}', (string) config('services.sync_inbox.address'), 2) + [1 => ''];
        $pattern = '/'.preg_quote($before, '/').'([a-z0-9]{12})'.preg_quote($after, '/').'/i';

        return preg_match($pattern, $recipient, $m) ? strtolower($m[1]) : null;
    }

    /**
     * @return list<string> allowed sender addresses, or "@domain" for a whole domain
     */
    public function senderList(): array
    {
        $items = preg_split('/[\s,;]+/', mb_strtolower((string) $this->senders), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map(fn (string $s) => str_contains($s, '@') ? $s : '@'.$s, $items)));
    }

    public function allows(?string $sender): bool
    {
        $sender = mb_strtolower(trim((string) $sender));
        if ($sender === '') {
            return false;
        }
        foreach ($this->senderList() as $allowed) {
            if ($allowed === $sender || (str_starts_with($allowed, '@') && str_ends_with($sender, $allowed))) {
                return true;
            }
        }

        return false;
    }

    public function allow(string $sender): void
    {
        $this->update(['senders' => implode("\n", array_unique([...$this->senderList(), mb_strtolower($sender)]))]);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(SyncRun::class, 'company_id', 'company_id');
    }
}
