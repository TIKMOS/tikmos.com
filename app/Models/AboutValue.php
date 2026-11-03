<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutValue extends Model
{
    /** @var list<string> */
    protected $fillable = ['key', 'title', 'description', 'icon', 'sort_order', 'is_visible'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_visible' => 'boolean'];
    }
}
