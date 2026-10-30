<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone asked to subscribe (from the beta, where there are no plans or checkout). Staff follow up and activate.
 */
#[Fillable(['company_id', 'user_id', 'name', 'company_name', 'email', 'phone', 'message', 'status', 'handled_at'])]
class InterestRequest extends Model
{
    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
