<?php

namespace App\Models;

use App\Enums\FlightDirection;
use App\Enums\FlightStatus;
use Database\Factories\FlightFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property FlightDirection $direction
 * @property FlightStatus $status
 * @property Carbon $departs_on
 * @property Carbon|null $arrives_on
 * @property string|null $capacity_kg
 * @property bool $is_public
 * @property bool $is_featured
 * @property Carbon|null $cutoff_at
 */
#[Fillable(['direction', 'departs_on', 'arrives_on', 'cutoff_at', 'capacity_kg', 'status', 'is_public', 'is_featured', 'notes', 'created_by'])]
class Flight extends Model
{
    /** @use HasFactory<FlightFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'direction' => FlightDirection::class,
            'status' => FlightStatus::class,
            'departs_on' => 'date',
            'arrives_on' => 'date',
            'capacity_kg' => 'decimal:2',
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'cutoff_at' => 'datetime',
        ];
    }

    /**
     * Le prochain départ à mettre en avant : un vol marqué « featured » sinon le prochain à venir.
     */
    public static function nextDeparture(): ?self
    {
        return self::query()->public()->upcoming()->where('is_featured', true)->first()
            ?? self::query()->public()->upcoming()->first();
    }

    /** La date de clôture des dépôts (cutoff_at sinon départ). */
    public function deadline(): Carbon
    {
        return $this->cutoff_at ?? $this->departs_on->copy()->startOfDay();
    }

    public function isClosed(): bool
    {
        return $this->deadline()->isPast();
    }

    /** @return HasMany<Parcel, $this> */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param  Builder<Flight>  $query */
    public function scopePublic(Builder $query): void
    {
        $query->where('is_public', true);
    }

    /** @param  Builder<Flight>  $query */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('departs_on', '>=', now()->toDateString())
            ->orderBy('departs_on');
    }
}
