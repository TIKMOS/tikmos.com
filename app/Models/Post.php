<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Publication;
use App\Media\HasStandardImages;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property Publication $status
 * @property Carbon|null $published_at
 * @property int|null $reading_time
 */
#[Fillable([
    'post_category_id', 'author_id', 'slug', 'title', 'excerpt', 'body',
    'reading_time', 'published_at', 'status', 'seo_title', 'seo_description',
])]
class Post extends Model implements HasMedia
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasStandardImages, InteractsWithMedia, SoftDeletes {
        HasStandardImages::registerMediaConversions insteadof InteractsWithMedia;
    }

    protected function casts(): array
    {
        return [
            'status' => Publication::class,
            'published_at' => 'datetime',
            'reading_time' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function coverUrl(?string $conversion = 'card'): ?string
    {
        return $this->getFirstMedia('cover')?->getUrl($conversion);
    }

    /** @return BelongsTo<PostCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Publié = statut publié ET date de publication passée (ou nulle).
     *
     * @param  Builder<self>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', Publication::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at');
    }
}
