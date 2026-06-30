<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewSource: string
{
    case Parcel = 'parcel';
    case Order = 'order';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Parcel => 'Colis',
            self::Order => 'Commande',
            self::Manual => 'Manuel',
        };
    }
}
