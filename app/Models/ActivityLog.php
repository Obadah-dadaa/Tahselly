<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable(['company_id', 'user_id', 'action', 'description', 'ip', 'meta'])]
class ActivityLog extends Model
{
    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function record(string $action, string $description, array $meta = [], ?int $companyId = null): self
    {
        $user = Auth::user();

        return static::create([
            'company_id' => $companyId ?? $user?->company_id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip' => request()?->ip(),
            'meta' => $meta ?: null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
