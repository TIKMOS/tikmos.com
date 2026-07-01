<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Commercial = 'commercial';
    case Editor = 'editor';
    case GestionnaireBoutique = 'gestionnaire_boutique';
    case Support = 'support';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super administrateur',
            self::Admin => 'Administrateur',
            self::Commercial => 'Commercial',
            self::Editor => 'Éditeur (contenu)',
            self::GestionnaireBoutique => 'Gestionnaire boutique',
            self::Support => 'Support',
            self::Client => 'Client',
        };
    }

    /**
     * Rôles de gestion (back-office, 2FA requise).
     *
     * @return array<int, self>
     */
    public static function management(): array
    {
        return [
            self::SuperAdmin,
            self::Admin,
            self::Commercial,
            self::Editor,
            self::GestionnaireBoutique,
            self::Support,
        ];
    }

    public function isManagement(): bool
    {
        return in_array($this, self::management(), true);
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(fn (self $r) => $r->value, self::cases());
    }
}
