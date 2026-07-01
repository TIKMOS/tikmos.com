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
 * @property bool $is_featured
 * @property int $position
 * @property array<int, array<string, string>>|null $socials
 * @property array<int, string>|null $funfacts
 */
#[Fillable(['name', 'role_title', 'bio', 'socials', 'funfacts', 'position', 'is_featured', 'is_active'])]
class TeamMember extends Model implements HasMedia
{
    use HasStandardImages, InteractsWithMedia {
        HasStandardImages::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'socials' => 'array',
            'funfacts' => 'array',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function photoUrl(): ?string
    {
        return $this->getFirstMedia('photo')?->getUrl();
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position');
    }
}
