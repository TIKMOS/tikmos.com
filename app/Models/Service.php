<?php

declare(strict_types=1);

namespace App\Models;

use App\Audit\Concerns\Auditable;
use App\Enums\Publication;
use App\Enums\ServiceCategory;
use App\Media\HasStandardImages;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property ServiceCategory $category
 * @property Publication $status
 * @property int $position
 */
#[Fillable([
    'category', 'slug', 'title', 'excerpt', 'body', 'price_label',
    'icon', 'position', 'status', 'seo_title', 'seo_description',
])]
class Service extends Model implements HasMedia
{
    /** @use HasFactory<ServiceFactory> */
    use Auditable, HasFactory, HasStandardImages, InteractsWithMedia, SoftDeletes {
        HasStandardImages::registerMediaConversions insteadof InteractsWithMedia;
    }

    /** @return array<int, string> */
    protected function auditAttributes(): array
    {
        return ['title', 'status', 'price_label', 'category'];
    }

    protected function auditGroup(): string
    {
        return 'contenu';
    }

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'status' => Publication::class,
            'position' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('illustration')->singleFile();
    }

    public function imageUrl(?string $conversion = 'card'): ?string
    {
        return $this->getFirstMedia('illustration')?->getUrl($conversion);
    }

    /** Icône effective : celle du service, sinon celle de la catégorie. */
    public function iconName(): string
    {
        return $this->icon ?: $this->category->icon();
    }

    /** @return HasMany<ServiceRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', Publication::Published->value)->orderBy('position');
    }

    /** @param  Builder<self>  $query */
    public function scopeCategory(Builder $query, ServiceCategory $category): void
    {
        $query->where('category', $category->value);
    }
}
