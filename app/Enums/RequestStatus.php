<?php

declare(strict_types=1);

namespace App\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case Converted = 'converted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouvelle',
            self::Quoted => 'Devis envoyé',
            self::Accepted => 'Acceptée',
            self::Converted => 'Convertie en colis',
            self::Rejected => 'Refusée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Quoted => 'warning',
            self::Accepted, self::Converted => 'success',
            self::Rejected => 'danger',
        };
    }
}
