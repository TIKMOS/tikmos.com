<?php

declare(strict_types=1);

namespace App\Enums;

enum ParcelOrigin: string
{
    case Paris = 'paris';
    case Cotonou = 'cotonou';

    public function label(): string
    {
        return match ($this) {
            self::Paris => 'Paris',
            self::Cotonou => 'Cotonou',
        };
    }
}
