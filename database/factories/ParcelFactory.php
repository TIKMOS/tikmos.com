<?php

namespace Database\Factories;

use App\Enums\FlightDirection;
use App\Enums\ParcelStatus;
use App\Enums\ParcelType;
use App\Enums\PaymentStatus;
use App\Models\Parcel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Parcel> */
class ParcelFactory extends Factory
{
    protected $model = Parcel::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $direction = fake()->randomElement(FlightDirection::cases());

        return [
            'reference' => 'SZ-'.now()->year.'-'.str_pad((string) fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'recipient_name' => fake()->name(),
            'recipient_phone' => fake()->e164PhoneNumber(),
            'recipient_city' => fake()->city(),
            'type' => fake()->randomElement(ParcelType::cases())->value,
            'weight_kg' => fake()->randomFloat(2, 0.5, 20),
            'price' => fake()->numberBetween(5000, 50000),
            'currency' => 'XOF',
            'origin' => $direction->origin()->value,
            'destination' => $direction->destination()->value,
            'status' => ParcelStatus::Registered->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'qr_token' => Str::random(40),
        ];
    }
}
