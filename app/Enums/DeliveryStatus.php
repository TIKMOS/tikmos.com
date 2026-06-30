<?php

declare(strict_types=1);

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case EnRoute = 'en_route';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À affecter',
            self::Assigned => 'Affectée',
            self::PickedUp => 'Prise en charge',
            self::EnRoute => 'En route',
            self::Delivered => 'Livrée',
            self::Failed => 'Échec',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Assigned, self::PickedUp, self::EnRoute => 'info',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }

    /**
     * Transitions autorisées pour le livreur.
     *
     * @return array<int, self>
     */
    public function nextStates(): array
    {
        return match ($this) {
            self::Assigned => [self::PickedUp, self::Failed],
            self::PickedUp => [self::EnRoute, self::Failed],
            self::EnRoute => [self::Delivered, self::Failed],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->nextStates(), true);
    }
}
