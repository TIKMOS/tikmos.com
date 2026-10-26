<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use JsonException;
use LogicException;
use RuntimeException;

class OrderNumberAllocator
{
    /** Premier accès SQL de la transaction de commande : écriture même sous SQLite. */
    public function lockCurrentYear(): int
    {
        $this->requireTransaction();
        $year = (int) now((string) config('app.timezone'))->format('Y');
        DB::table('order_number_sequences')->upsert(
            [['year' => $year, 'last_number' => 0]], ['year'], ['year'],
        );
        $row = DB::table('order_number_sequences')->where('year', $year)->lockForUpdate()->first();
        if ($row === null) {
            throw new RuntimeException('Compteur annuel indisponible. Aucun numéro attribué.');
        }
        if ((int) $row->last_number === 0 && DB::table('orders')
            ->whereRaw('LOWER(number) LIKE ?', ['tkm-cmd-'.$year.'-%'])->exists()) {
            throw new RuntimeException('Compteur annuel non initialisé pour des commandes historiques. Réconciliez les plafonds avant de poursuivre.');
        }

        return $year;
    }

    /** À appeler dans la même transaction, après lockCurrentYear(). */
    public function nextForYear(int $year): string
    {
        $this->requireTransaction();
        $row = DB::table('order_number_sequences')->where('year', $year)->lockForUpdate()->first();
        if ($row === null || (int) $row->last_number < 0 || (int) $row->last_number === PHP_INT_MAX) {
            throw new RuntimeException('Compteur annuel absent ou épuisé. Aucun numéro attribué.');
        }
        $next = (int) $row->last_number + 1;
        DB::table('order_number_sequences')->where('year', $year)->update(['last_number' => $next]);

        return sprintf('TKM-CMD-%04d-%05d', $year, $next);
    }

    /**
     * Prévalidation de migration, avant tout DDL MySQL.
     * Les références d'autres formats restent intactes ; un préfixe TKM-CMD mal
     * formé est refusé, sans le recopier dans le message d'erreur.
     *
     * @return array<int, int>
     */
    public function installationCounters(): array
    {
        $history = [];
        foreach (DB::table('orders')->select('number')->orderBy('id')->cursor() as $order) {
            $number = (string) $order->number;
            if (strncasecmp($number, 'TKM-CMD-', 8) !== 0) {
                continue;
            }
            if (preg_match('/\ATKM-CMD-([0-9]{4})-([0-9]{5,})\z/i', $number, $parts) !== 1
                || (int) $parts[1] < 1) {
                throw new RuntimeException('Numérotation historique incompatible. Vérification manuelle requise, aucune commande modifiée.');
            }
            $digits = ltrim($parts[2], '0');
            $maximum = (string) PHP_INT_MAX;
            if ($digits === '' || strlen($digits) > strlen($maximum)
                || (strlen($digits) === strlen($maximum) && strcmp($digits, $maximum) > 0)) {
                throw new RuntimeException('Séquence historique hors limites. Vérification manuelle requise, aucune commande modifiée.');
            }
            $year = (int) $parts[1];
            $history[$year] = max($history[$year] ?? 0, (int) $digits);
        }

        $floors = $this->configuredFloors();
        foreach ($history as $year => $maximum) {
            if (! array_key_exists($year, $floors) || $floors[$year] < $maximum) {
                throw new RuntimeException('Initialisation requise : fournissez ORDER_NUMBER_SEQUENCE_FLOORS avec un plafond vérifié pour chaque année historique, au moins égal au maximum présent. Incluez les numéros supprimés connus. Aucune commande modifiée.');
            }
        }

        return $floors;
    }

    /** @return array<int, int> */
    private function configuredFloors(): array
    {
        $raw = config('orders.number_sequence_floors');
        if ($raw === null || $raw === '') {
            return [];
        }
        try {
            $values = is_string($raw) ? json_decode($raw, flags: JSON_THROW_ON_ERROR) : null;
        } catch (JsonException) {
            throw new RuntimeException('ORDER_NUMBER_SEQUENCE_FLOORS doit être un objet JSON année:entier, sans données client.');
        }
        if (! $values instanceof \stdClass) {
            throw new RuntimeException('ORDER_NUMBER_SEQUENCE_FLOORS doit être un objet JSON année:entier.');
        }
        $floors = [];
        foreach (get_object_vars($values) as $year => $value) {
            if (preg_match('/\A[0-9]{4}\z/', (string) $year) !== 1 || (int) $year < 1
                || ! is_int($value) || $value < 0) {
                throw new RuntimeException('Plafonds de numérotation invalides : année sur quatre chiffres et entier non négatif requis.');
            }
            $floors[(int) $year] = $value;
        }

        return $floors;
    }

    private function requireTransaction(): void
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('L’attribution du numéro exige la transaction de commande.');
        }
    }
}
