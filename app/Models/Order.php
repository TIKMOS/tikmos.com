<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Payments\Contracts\Payable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property OrderStatus $status
 * @property PaymentMethod $payment_method
 * @property OrderPaymentStatus $payment_status
 * @property int $total
 * @property int $subtotal
 * @property int $delivery_fee
 * @property Carbon|null $received_confirmed_at
 */
#[Fillable([
    'number', 'user_id', 'status', 'payment_method', 'payment_status',
    'subtotal', 'delivery_fee', 'total', 'contact_name', 'contact_phone',
    'delivery_address', 'delivery_city', 'notes',
    'confirmed_at', 'shipped_at', 'delivered_at', 'received_confirmed_at',
])]
class Order extends Model implements Payable
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => OrderPaymentStatus::class,
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
            'confirmed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'received_confirmed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return MorphMany<Payment, $this> */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function payableAmount(): int
    {
        return $this->total;
    }

    /**
     * Réaction au paiement en ligne confirmé (idempotente : appelée une seule
     * fois par PaymentService::settle).
     */
    public function onPaymentSucceeded(Payment $payment): void
    {
        $this->update([
            'payment_status' => OrderPaymentStatus::Paid,
            'confirmed_at' => $this->confirmed_at ?? now(),
            'status' => $this->status === OrderStatus::Pending ? OrderStatus::Confirmed : $this->status,
        ]);
    }

    public function totalLabel(): string
    {
        return number_format($this->total, 0, ',', ' ').' FCFA';
    }

    /** @param  Builder<self>  $query */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * Étapes de la timeline pour x-tk.stepper.
     *
     * @return array<int, array<string, mixed>>
     */
    public function timelineSteps(): array
    {
        if ($this->status === OrderStatus::Cancelled) {
            return [['label' => 'Annulée', 'state' => 'current']];
        }

        $timeline = OrderStatus::timeline();
        $currentIndex = array_search($this->status, $timeline, true);

        $steps = [];
        foreach ($timeline as $index => $status) {
            $steps[] = [
                'label' => $status->label(),
                'state' => $currentIndex === false ? 'todo'
                    : ($index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'todo')),
            ];
        }

        return $steps;
    }
}
