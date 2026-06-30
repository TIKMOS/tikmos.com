<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property bool $is_published
 * @property int $position
 */
#[Fillable(['question', 'answer', 'category', 'position', 'is_published'])]
class FaqItem extends Model
{
    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    /** @param  Builder<self>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderBy('position');
    }
}
