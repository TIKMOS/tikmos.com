<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Mode global : 'cod' (à la livraison), 'online' (KKiaPay) ou 'both' (choix client).
        $this->migrator->add('payments.mode', 'cod');

        // Passerelle KKiaPay — sandbox par défaut, clés vides (fallback env/config).
        $this->migrator->add('payments.kkiapay_sandbox', true);
        $this->migrator->add('payments.kkiapay_public_key', '');
        $this->migrator->addEncrypted('payments.kkiapay_private_key', '');
        $this->migrator->addEncrypted('payments.kkiapay_secret', '');
    }
};
