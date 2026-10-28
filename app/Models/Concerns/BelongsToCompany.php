<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes every query to the signed-in user's company and stamps company_id on create.
 * Developers (platform staff) are not scoped and see all tenants.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $user = Auth::user();
            if ($user && $user->company_id && ! $user->isPlatformStaff()) {
                $builder->where($builder->getModel()->getTable().'.company_id', $user->company_id);
            }
        });

        static::creating(function ($model): void {
            if (! $model->company_id && Auth::user()?->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
