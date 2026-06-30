<?php

declare(strict_types=1);

namespace App\Enums;

enum FlightStatus: string
{
    case Scheduled = 'scheduled';
    case Boarding = 'boarding';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Planifié',
            self::Boarding => 'Enregistrement',
            self::InTransit => 'En vol',
            self::Arrived => 'Arrivé',
            self::Closed => 'Clôturé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Scheduled => 'neutral',
            self::Boarding => 'warning',
            self::InTransit => 'info',
            self::Arrived => 'success',
            self::Closed => 'neutral',
        };
    }
}
