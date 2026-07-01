<?php

declare(strict_types=1);

namespace App\Payments\Enums;

enum PaymentState: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Succeeded => 'Payé',
            self::Failed => 'Échoué',
            self::Refunded => 'Remboursé',
            self::Cancelled => 'Annulé',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Succeeded => 'success',
            self::Failed, self::Cancelled => 'danger',
            self::Refunded => 'info',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
