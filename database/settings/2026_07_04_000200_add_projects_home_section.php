<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Insère « projects » dans l'ordre des sections de l'accueil (après « steps »).
        $this->migrator->update('homepage.sections_order', function ($order) {
            $order = array_values(array_filter((array) $order, fn ($s) => $s !== 'projects'));
            $pos = array_search('steps', $order, true);
            $insertAt = $pos === false ? count($order) : $pos + 1;
            array_splice($order, $insertAt, 0, 'projects');

            return $order;
        });

        $this->migrator->update('homepage.sections_enabled', fn ($v) => array_merge((array) $v, ['projects' => true]));
    }
};
