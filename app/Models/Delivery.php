<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property DeliveryStatus $status
 * @property int $commission_amount
 * @property Carbon|null $delivered_at
 * @property Carbon|null $picked_up_at
 */
#[Fillable([
    'order_id', 'courier_id', 'livreur_id', 'status', 'pickup_location',
    'dropoff_address', 'dropoff_city', 'failed_reason', 'commission_amount',
    'assigned_at', 'picked_up_at', 'en_route_at', 'delivered_at',
])]
class Delivery extends Model
{
    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'commission_amount' => 'integer',
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'en_route_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Courier, $this> */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function livreur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'livreur_id');
    }

    /** Durée de livraison en minutes (prise en charge → livré). */
    public function durationMinutes(): ?int
    {
        if ($this->picked_up_at && $this->delivered_at) {
            return (int) $this->picked_up_at->diffInMinutes($this->delivered_at);
        }

        return null;
    }

    /** @param  Builder<self>  $query */
    public function scopeForLivreur(Builder $query, User $user): void
    {
        $query->where('livreur_id', $user->id);
    }
}
