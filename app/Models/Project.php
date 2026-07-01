<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Publication;
use App\Media\HasStandardImages;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Publication $status
 * @property int $position
 * @property int|null $year
 */
#[Fillable([
    'slug', 'title', 'excerpt', 'body', 'client_name', 'category',
    'results', 'year', 'position', 'status', 'seo_title', 'seo_description',
])]
class Project extends Model implements HasMedia
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasStandardImages, InteractsWithMedia, SoftDeletes {
        HasStandardImages::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected function casts(): array
    {
        return [
            'status' => Publication::class,
            'position' => 'integer',
            'year' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }

    public function coverUrl(?string $conversion = 'card'): ?string
    {
        return $this->getFirstMedia('gallery')?->getUrl($conversion);
    }

    /** @return array<int, string> */
    public function galleryUrls(): array
    {
        return $this->getMedia('gallery')->map(fn (Media $m) => $m->getUrl('card'))->all();
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', Publication::Published->value)->orderBy('position');
    }
}
