<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Accueil recentré : hero (fixe) → réalisations → équipe → CTA (fixe). Partenaires = layout.
        $this->migrator->update('homepage.sections_order', fn () => ['projects', 'team']);

        // CTA sans référence à la boutique (« paire »).
        $this->migrator->update('homepage.cta_title', fn () => 'Un projet, une formation en vue ?');
    }
};
