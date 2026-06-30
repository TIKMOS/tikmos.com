<?php

declare(strict_types=1);

namespace App\Enums;

enum CommissionType: string
{
    case Percent = 'percent';
    case Flat = 'flat';
    case PerDelivery = 'per_delivery';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Pourcentage du total',
            self::Flat => 'Montant fixe par commande',
            self::PerDelivery => 'Décompte par livraison',
            self::None => 'Aucune commission',
        };
    }

    /** Décrit l'unité de la valeur saisie. */
    public function valueHint(): string
    {
        return match ($this) {
            self::Percent => '% (0–100)',
            self::Flat, self::PerDelivery => 'FCFA par livraison',
            self::None => '—',
        };
    }
}
