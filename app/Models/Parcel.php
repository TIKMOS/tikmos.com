<?php

namespace App\Models;

use App\Enums\ParcelOrigin;
use App\Enums\ParcelStatus;
use App\Enums\ParcelType;
use App\Enums\PaymentStatus;
use Database\Factories\ParcelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $reference
 * @property int|null $sender_id
 * @property int|null $agent_id
 * @property string $recipient_name
 * @property ParcelType $type
 * @property ParcelStatus $status
 * @property PaymentStatus $payment_status
 * @property ParcelOrigin $origin
 * @property ParcelOrigin $destination
 * @property string|null $qr_token
 * @property Carbon|null $delivered_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'reference', 'flight_id', 'sender_id', 'sender_name', 'sender_phone',
    'recipient_name', 'recipient_phone', 'recipient_city', 'recipient_country',
    'type', 'weight_kg', 'declared_value', 'price', 'currency',
    'origin', 'destination', 'status', 'payment_status', 'agent_id',
    'qr_token', 'delivered_at', 'notes',
])]
class Parcel extends Model
{
    /** @use HasFactory<ParcelFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => ParcelType::class,
            'status' => ParcelStatus::class,
            'payment_status' => PaymentStatus::class,
            'origin' => ParcelOrigin::class,
            'destination' => ParcelOrigin::class,
            'weight_kg' => 'decimal:2',
            'declared_value' => 'decimal:2',
            'price' => 'decimal:2',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Flight, $this> */
    public function flight(): BelongsTo
    {
        return $this->belongsTo(Flight::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** @return BelongsTo<User, $this> */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** @return HasMany<ParcelEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(ParcelEvent::class)->orderBy('happened_at');
    }

    /** @return MorphMany<Review, $this> */
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /** @param  Builder<Parcel>  $query */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('sender_id', $user->id);
    }

    /** @param  Builder<Parcel>  $query */
    public function scopeStatus(Builder $query, ParcelStatus $status): void
    {
        $query->where('status', $status->value);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /**
     * Construit les étapes de la timeline pour x-tk.stepper (relation `events` à charger au préalable).
     *
     * @return array<int, array<string, mixed>>
     */
    public function timelineSteps(): array
    {
        $timeline = ParcelStatus::timeline();
        $events = $this->events->keyBy(fn ($event) => $event->status->value);

        $order = [];
        foreach ($timeline as $index => $status) {
            $order[$status->value] = $index;
        }
        $currentIndex = $order[$this->status->value] ?? null;

        $steps = [];
        foreach ($timeline as $index => $status) {
            $event = $events->get($status->value);

            $state = $currentIndex !== null
                ? ($index < $currentIndex ? 'done' : ($index === $currentIndex ? 'current' : 'todo'))
                : ($event ? 'done' : 'todo');

            $steps[] = [
                'label' => $status->label(),
                'date' => $event?->happened_at?->format('d/m/Y'),
                'location' => $event?->location,
                'state' => $state,
            ];
        }

        return $steps;
    }
}
