<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewSource: string
{
    case Order = 'order';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Commande',
            self::Manual => 'Manuel',
        };
    }
}
