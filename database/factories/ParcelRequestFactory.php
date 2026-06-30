<?php

namespace Database\Factories;

use App\Enums\FlightDirection;
use App\Enums\ParcelType;
use App\Enums\RequestStatus;
use App\Models\ParcelRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ParcelRequest> */
class ParcelRequestFactory extends Factory
{
    protected $model = ParcelRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $direction = fake()->randomElement(FlightDirection::cases());

        return [
            'type' => fake()->randomElement(ParcelType::cases())->value,
            'weight_estimate' => fake()->randomFloat(2, 0.5, 15),
            'origin' => $direction->origin()->value,
            'destination' => $direction->destination()->value,
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->e164PhoneNumber(),
            'message' => fake()->sentence(),
            'status' => RequestStatus::New->value,
        ];
    }
}
