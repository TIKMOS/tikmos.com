<?php

declare(strict_types=1);

namespace App\Enums;

enum ParcelType: string
{
    case Vetements = 'vetements';
    case Telephone = 'telephone';
    case Alimentaire = 'alimentaire';
    case Documents = 'documents';
    case Cosmetiques = 'cosmetiques';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Vetements => 'Vêtements',
            self::Telephone => 'Téléphone / high-tech',
            self::Alimentaire => 'Produits alimentaires',
            self::Documents => 'Documents',
            self::Cosmetiques => 'Parfums / cosmétiques',
            self::Autre => 'Autre',
        };
    }
}
