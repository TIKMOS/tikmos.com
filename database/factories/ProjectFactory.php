<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Publication;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(3, true));

        return [
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => $title,
            'excerpt' => $this->faker->sentence(),
            'body' => '<p>'.$this->faker->paragraph().'</p>',
            'client_name' => $this->faker->optional()->company(),
            'category' => $this->faker->randomElement(['Data / IA', 'Application métier', 'Formation', 'Automatisation']),
            'results' => $this->faker->optional()->sentence(),
            'year' => $this->faker->numberBetween(2022, 2026),
            'position' => $this->faker->numberBetween(0, 20),
            'status' => Publication::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Publication::Draft]);
    }
}
