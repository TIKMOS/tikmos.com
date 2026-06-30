<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property bool $is_active
 * @property CommissionType $commission_type
 * @property int $commission_value
 * @property array<int, string>|null $zones
 */
#[Fillable([
    'name', 'slug', 'contact_phone', 'contact_email', 'zones',
    'is_active', 'commission_type', 'commission_value', 'notes',
])]
class Courier extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'commission_type' => CommissionType::class,
            'commission_value' => 'integer',
            'zones' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->nonQueued()->width(160)->height(160);
    }

    public function logoUrl(): ?string
    {
        return $this->getFirstMedia('logo')?->getUrl();
    }

    /** @return HasMany<User, $this> */
    public function livreurs(): HasMany
    {
        return $this->hasMany(User::class, 'courier_id');
    }

    /** @return HasMany<Delivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('name');
    }
}
