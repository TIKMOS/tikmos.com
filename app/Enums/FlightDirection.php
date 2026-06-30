<?php

declare(strict_types=1);

namespace App\Enums;

enum FlightDirection: string
{
    case ParisCotonou = 'paris_cotonou';
    case CotonouParis = 'cotonou_paris';

    public function label(): string
    {
        return match ($this) {
            self::ParisCotonou => 'Paris → Cotonou',
            self::CotonouParis => 'Cotonou → Paris',
        };
    }

    public function origin(): ParcelOrigin
    {
        return match ($this) {
            self::ParisCotonou => ParcelOrigin::Paris,
            self::CotonouParis => ParcelOrigin::Cotonou,
        };
    }

    public function destination(): ParcelOrigin
    {
        return match ($this) {
            self::ParisCotonou => ParcelOrigin::Cotonou,
            self::CotonouParis => ParcelOrigin::Paris,
        };
    }
}
