<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property QuoteStatus $status
 * @property int $amount
 */
#[Fillable([
    'service_request_id', 'reference', 'amount', 'currency',
    'valid_until', 'body', 'status', 'sent_at', 'decided_at',
])]
class Quote extends Model
{
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'amount' => 'integer',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ServiceRequest, $this> */
    public function request(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function amountLabel(): string
    {
        return number_format($this->amount, 0, ',', ' ').' '.$this->currency;
    }
}
