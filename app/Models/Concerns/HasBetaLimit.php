<?php

namespace App\Models\Concerns;

use App\Exceptions\BetaLimitReached;
use App\Support\Edition;

/**
 * In the beta, refuses to create a record once the company holds as many as the beta allows.
 * The using model defines betaLimitKey() and betaLimitMessage().
 */
trait HasBetaLimit
{
    protected static function bootHasBetaLimit(): void
    {
        static::creating(function (self $model): void {
            if (! Edition::isBeta() || ! $model->company_id) {
                return;
            }
            $limit = Edition::limit($model->betaLimitKey());
            $count = static::withoutGlobalScopes()->where('company_id', $model->company_id)->count();
            if ($count >= $limit) {
                throw new BetaLimitReached($model->betaLimitMessage($limit));
            }
        });
    }
}
