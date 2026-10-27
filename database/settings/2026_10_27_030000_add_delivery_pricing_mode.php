<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Spatie\LaravelSettings\SettingsRepositories\SettingsRepository;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $fee = app(SettingsRepository::class)->getPropertyPayload('shop', 'default_delivery_fee');
        // Un tarif positif était déjà explicite ; zéro ne prouve pas une gratuité.
        $this->migrator->add('delivery.mode', is_int($fee) && $fee > 0 ? 'fixed' : 'unknown');
    }

    public function down(): void
    {
        $this->migrator->delete('delivery.mode');
    }
};
