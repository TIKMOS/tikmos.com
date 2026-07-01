<?php

declare(strict_types=1);

namespace App\Models;

use App\Media\HasStandardImages;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property bool $is_active
 * @property int $position
 */
#[Fillable(['name', 'url', 'blurb', 'position', 'is_active'])]
class Partner extends Model implements HasMedia
{
    use HasStandardImages, InteractsWithMedia {
        HasStandardImages::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function logoUrl(): ?string
    {
        return $this->getFirstMedia('logo')?->getUrl();
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position');
    }
}
