<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentMethod: string
{
    case Cod = 'cod';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'Paiement à la livraison',
            self::Online => 'Paiement en ligne',
        };
    }
}
