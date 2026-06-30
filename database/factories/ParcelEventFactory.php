<?php

namespace Database\Factories;

use App\Enums\ParcelStatus;
use App\Models\Parcel;
use App\Models\ParcelEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ParcelEvent> */
class ParcelEventFactory extends Factory
{
    protected $model = ParcelEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'parcel_id' => Parcel::factory(),
            'status' => ParcelStatus::Registered->value,
            'comment' => null,
            'location' => null,
            'happened_at' => now(),
        ];
    }
}
