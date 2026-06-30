<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceCategory: string
{
    case Formation = 'formation';
    case Conception = 'conception';
    case Installation = 'installation';
    case Assistance = 'assistance';
    case Orientation = 'orientation';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Formation => 'Formation',
            self::Conception => 'Conception de solutions',
            self::Installation => 'Installation informatique',
            self::Assistance => 'Assistance administrative',
            self::Orientation => 'Orientation professionnelle',
            self::Autre => 'Autre service',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Formation => 'graduation-cap',
            self::Conception => 'layers',
            self::Installation => 'wrench',
            self::Assistance => 'clipboard-list',
            self::Orientation => 'compass',
            self::Autre => 'sparkles',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
