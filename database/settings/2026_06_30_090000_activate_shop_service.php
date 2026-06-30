<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('homepage.services', function ($services): array {
            // La valeur peut arriver en stdClass (décodage JSON) : on normalise en tableaux.
            $services = json_decode((string) json_encode($services), true) ?: [];

            foreach ($services as &$service) {
                if (($service['icon'] ?? '') === 'dumbbell' || str_starts_with((string) ($service['title'] ?? ''), 'Boutique')) {
                    $service['title'] = 'Boutique';
                    $service['desc'] = 'Protéines et compléments sélectionnés, livrés près de chez vous.';
                    $service['href'] = '/boutique';
                    $service['soon'] = false;
                }
            }

            return $services;
        });
    }
};
