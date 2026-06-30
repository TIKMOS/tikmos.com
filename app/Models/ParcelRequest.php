<?php

namespace App\Models;

use App\Enums\ParcelOrigin;
use App\Enums\ParcelType;
use App\Enums\RequestStatus;
use Database\Factories\ParcelRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ParcelType $type
 * @property RequestStatus $status
 * @property ParcelOrigin $origin
 * @property ParcelOrigin $destination
 * @property int|null $user_id
 */
#[Fillable([
    'user_id', 'type', 'weight_estimate', 'origin', 'destination',
    'recipient_name', 'recipient_phone', 'message', 'status', 'converted_parcel_id',
])]
class ParcelRequest extends Model
{
    /** @use HasFactory<ParcelRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ParcelType::class,
            'status' => RequestStatus::class,
            'origin' => ParcelOrigin::class,
            'destination' => ParcelOrigin::class,
            'weight_estimate' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Parcel, $this> */
    public function convertedParcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class, 'converted_parcel_id');
    }

    /** @param  Builder<ParcelRequest>  $query */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }
}
