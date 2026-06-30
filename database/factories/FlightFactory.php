<?php

namespace Database\Factories;

use App\Enums\FlightDirection;
use App\Enums\FlightStatus;
use App\Models\Flight;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Flight> */
class FlightFactory extends Factory
{
    protected $model = Flight::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'direction' => fake()->randomElement(FlightDirection::cases())->value,
            'departs_on' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'arrives_on' => null,
            'capacity_kg' => fake()->randomElement([100, 150, 200]),
            'status' => FlightStatus::Scheduled->value,
            'is_public' => true,
            'notes' => null,
        ];
    }

    public function private(): static
    {
        return $this->state(fn () => ['is_public' => false]);
    }
}
