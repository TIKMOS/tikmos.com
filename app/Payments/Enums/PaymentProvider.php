<?php

declare(strict_types=1);

namespace App\Payments\Enums;

enum PaymentProvider: string
{
    case Kkiapay = 'kkiapay';
    case Cod = 'cod';

    public function label(): string
    {
        return match ($this) {
            self::Kkiapay => 'KKiaPay (Mobile Money / Carte)',
            self::Cod => 'Paiement à la livraison',
        };
    }

    public function isOnline(): bool
    {
        return $this === self::Kkiapay;
    }
}
