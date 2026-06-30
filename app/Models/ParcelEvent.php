<?php

namespace App\Models;

use App\Enums\ParcelStatus;
use Database\Factories\ParcelEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property ParcelStatus $status
 * @property string|null $comment
 * @property string|null $location
 * @property Carbon|null $happened_at
 */
#[Fillable(['parcel_id', 'status', 'comment', 'location', 'created_by', 'happened_at'])]
class ParcelEvent extends Model
{
    /** @use HasFactory<ParcelEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ParcelStatus::class,
            'happened_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Parcel, $this> */
    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
