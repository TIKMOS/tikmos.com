<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Publication;
use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        $title = ucfirst($this->faker->unique()->words(3, true));

        return [
            'category' => $this->faker->randomElement(ServiceCategory::cases()),
            'slug' => Str::slug($title).'-'.$this->faker->unique()->numberBetween(1, 99999),
            'title' => $title,
            'excerpt' => $this->faker->sentence(),
            'body' => '<p>'.$this->faker->paragraph().'</p>',
            'price_label' => $this->faker->randomElement(['Sur devis', 'À partir de 150 000 XOF', null]),
            'icon' => null,
            'position' => $this->faker->numberBetween(0, 20),
            'status' => Publication::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Publication::Draft]);
    }

    public function category(ServiceCategory $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}
