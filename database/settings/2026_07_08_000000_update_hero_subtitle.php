<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->update('homepage.hero_subtitle', fn () => "Professeur certifié en mathématiques et ingénieur data/IA. J'accompagne entreprises et particuliers — solutions sur mesure, formations, pour vos projets ambitieux.");
    }
};
