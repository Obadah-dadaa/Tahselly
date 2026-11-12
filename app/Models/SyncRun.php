<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One received report and what applying it did. "rejected" runs were held back (unknown sender, or a report
 * that would close most invoices at once) and can be applied by the owner; "failed" ones could not be read.
 */
#[Fillable(['company_id', 'source', 'sender', 'filename', 'path', 'status', 'rows_total', 'added', 'updated', 'closed', 'failed', 'message', 'headers', 'errors'])]
class SyncRun extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return ['headers' => 'array', 'errors' => 'array'];
    }

    public function isHeldBack(): bool
    {
        return $this->status === 'rejected' && $this->path !== null;
    }
}
