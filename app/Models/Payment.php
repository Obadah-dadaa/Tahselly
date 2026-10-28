<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'invoice_id', 'user_id', 'amount', 'paid_on', 'method', 'reference'])]
class Payment extends Model
{
    use BelongsToCompany;

    /** @var array<string, string> */
    public const METHODS = [
        'bank_transfer' => 'تحويل بنكي',
        'cash' => 'نقداً',
        'cheque' => 'شيك',
        'card' => 'بطاقة',
    ];

    protected function casts(): array
    {
        return ['paid_on' => 'date', 'amount' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
