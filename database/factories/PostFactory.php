<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Publication;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(4, true));

        return [
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => $title,
            'excerpt' => $this->faker->sentence(),
            'body' => '<p>'.$this->faker->paragraphs(3, true).'</p>',
            'reading_time' => $this->faker->numberBetween(2, 8),
            'published_at' => now()->subDays($this->faker->numberBetween(1, 60)),
            'status' => Publication::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Publication::Draft]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['status' => Publication::Published, 'published_at' => now()->addWeek()]);
    }
}
