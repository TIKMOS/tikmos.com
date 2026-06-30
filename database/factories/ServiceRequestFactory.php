<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->numerify('+229 ## ## ## ##'),
            'company' => $this->faker->optional()->company(),
            'message' => $this->faker->paragraph(),
            'budget_hint' => $this->faker->optional()->randomElement(['< 500k', '500k – 2M', '> 2M']),
            'status' => ServiceRequestStatus::Sent,
        ];
    }

    public function status(ServiceRequestStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
