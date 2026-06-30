<?php

declare(strict_types=1);

namespace App\Enums;

enum ParcelStatus: string
{
    case Registered = 'registered';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case ReadyForPickup = 'ready_for_pickup';
    case Delivered = 'delivered';
    case Returned = 'returned';
    case Incident = 'incident';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Enregistré',
            self::InTransit => 'En transit',
            self::Arrived => 'Arrivé',
            self::ReadyForPickup => 'À récupérer',
            self::Delivered => 'Livré',
            self::Returned => 'Retourné',
            self::Incident => 'Incident',
        };
    }

    /** Ton charte (cf. x-tk.badge). */
    public function tone(): string
    {
        return match ($this) {
            self::Registered => 'neutral',
            self::InTransit, self::Arrived => 'info',
            self::ReadyForPickup => 'warning',
            self::Delivered => 'success',
            self::Returned => 'neutral',
            self::Incident => 'danger',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned], true);
    }

    /** @return array<int, self> */
    public function nextStates(): array
    {
        return match ($this) {
            self::Registered => [self::InTransit, self::Incident],
            self::InTransit => [self::Arrived, self::Incident],
            self::Arrived => [self::ReadyForPickup, self::Incident],
            self::ReadyForPickup => [self::Delivered, self::Returned, self::Incident],
            self::Incident => [self::InTransit, self::Arrived, self::ReadyForPickup, self::Delivered, self::Returned],
            self::Delivered, self::Returned => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->nextStates(), true);
    }

    /**
     * Étapes de la timeline publique (ordre nominal).
     *
     * @return array<int, self>
     */
    public static function timeline(): array
    {
        return [self::Registered, self::InTransit, self::Arrived, self::ReadyForPickup, self::Delivered];
    }
}
