<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property QuoteStatus $status
 * @property int $amount
 * @property Carbon|null $valid_until
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

    /** Une date limite inclut toute la journée dans le fuseau de l'application. */
    public function isExpired(): bool
    {
        if ($this->status === QuoteStatus::Expired) {
            return true;
        }

        if ($this->status !== QuoteStatus::Sent || $this->valid_until === null) {
            return false;
        }

        $timezone = config('app.timezone');
        $deadline = Carbon::parse($this->valid_until->format('Y-m-d'), $timezone)->endOfDay();

        return now($timezone)->greaterThan($deadline);
    }

    public function displayStatus(): QuoteStatus
    {
        return $this->isExpired() ? QuoteStatus::Expired : $this->status;
    }

    public function hasDecision(): bool
    {
        return in_array($this->status, [QuoteStatus::Accepted, QuoteStatus::Refused], true);
    }
}
