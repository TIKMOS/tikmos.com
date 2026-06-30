<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Flight;

class FlightService
{
    /** Poids total des colis rattachés au vol (kg). */
    public function load(Flight $flight): float
    {
        return (float) $flight->parcels()->sum('weight_kg');
    }

    public function remainingCapacity(Flight $flight): ?float
    {
        if ($flight->capacity_kg === null) {
            return null;
        }

        return (float) $flight->capacity_kg - $this->load($flight);
    }

    public function isOverCapacity(Flight $flight): bool
    {
        $remaining = $this->remainingCapacity($flight);

        return $remaining !== null && $remaining < 0;
    }
}
