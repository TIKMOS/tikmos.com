<?php

declare(strict_types=1);

namespace App\Models;

use App\Media\HasStandardImages;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property bool $is_active
 * @property bool $is_featured
 * @property int $price
 * @property int|null $compare_at_price
 * @property int|null $stock
 * @property int $position
 */
#[Fillable([
    'category_id', 'name', 'brand', 'slug', 'description', 'sizes', 'price', 'compare_at_price',
    'stock', 'is_active', 'is_featured', 'position',
])]
class Product extends Model implements HasMedia
{
    use HasStandardImages, InteractsWithMedia, SoftDeletes {
        HasStandardImages::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sizes' => 'array',
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
        ];
    }

    /** @return list<string> */
    public function sizeList(): array
    {
        $sizes = [];

        foreach ((array) $this->sizes as $size) {
            $size = trim((string) $size);
            if ($size !== '') {
                $sizes[] = $size;
            }
        }

        return $sizes;
    }

    public function hasSizes(): bool
    {
        return $this->sizeList() !== [];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function mainImageUrl(?string $conversion = 'card'): ?string
    {
        return $this->getFirstMedia('gallery')?->getUrl($conversion);
    }

    /** @return array<int, string> */
    public function galleryUrls(): array
    {
        return $this->getMedia('gallery')->map(fn (Media $m) => $m->getUrl('card'))->all();
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ($this->stock === null || $this->stock > 0);
    }

    public function isOutOfStock(): bool
    {
        return $this->stock !== null && $this->stock <= 0;
    }

    /** Prix formaté en FCFA (XOF entier). */
    public function priceLabel(): string
    {
        return number_format($this->price, 0, ',', ' ').' FCFA';
    }

    public function compareAtLabel(): ?string
    {
        return $this->compare_at_price ? number_format($this->compare_at_price, 0, ',', ' ').' FCFA' : null;
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<self>  $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_active', true)->where('is_featured', true);
    }
}
