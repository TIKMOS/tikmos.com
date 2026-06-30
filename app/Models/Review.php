<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewSource;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property ReviewStatus $status
 * @property ReviewSource $source
 * @property bool $is_featured
 * @property int $rating
 */
#[Fillable([
    'user_id', 'reviewable_type', 'reviewable_id', 'author_name', 'author_city',
    'rating', 'body', 'source', 'status', 'is_featured', 'published_at',
])]
class Review extends Model
{
    protected function casts(): array
    {
        return [
            'source' => ReviewSource::class,
            'status' => ReviewStatus::class,
            'is_featured' => 'boolean',
            'rating' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', ReviewStatus::Approved->value);
    }

    /** @param  Builder<self>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', ReviewStatus::Pending->value);
    }

    /** @param  Builder<self>  $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true)
            ->where('status', ReviewStatus::Approved->value)
            ->orderByDesc('published_at');
    }
}
