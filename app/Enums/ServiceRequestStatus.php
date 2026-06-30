<?php

declare(strict_types=1);

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case Sent = 'sent';
    case Studying = 'studying';
    case Quoted = 'quoted';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Refused = 'refused';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Envoyée',
            self::Studying => 'En étude',
            self::Quoted => 'Devis envoyé',
            self::Accepted => 'Acceptée',
            self::InProgress => 'En cours',
            self::Delivered => 'Livrée',
            self::Refused => 'Refusée',
            self::Cancelled => 'Annulée',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Sent => 'info',
            self::Studying, self::Quoted => 'warning',
            self::Accepted, self::InProgress => 'info',
            self::Delivered => 'success',
            self::Refused, self::Cancelled => 'danger',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Delivered, self::Refused, self::Cancelled], true);
    }

    /** @return array<int, self> */
    public static function pipeline(): array
    {
        return [self::Sent, self::Studying, self::Quoted, self::Accepted, self::InProgress, self::Delivered];
    }
}
