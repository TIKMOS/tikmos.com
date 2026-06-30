<?php

use App\Models\LegalDocument;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        LegalDocument::updateOrCreate(['key' => 'cgu'], [
            'title' => 'Conditions générales',
            'is_published' => true,
            'body' => <<<'HTML'
<h2>Objet</h2>
<p>Les présentes conditions encadrent l'utilisation des services SEZY : envoi de colis, achat &amp; commande, accompagnement à la mobilité et boutique compléments.</p>
<h2>Colis acceptés</h2>
<p>Les articles illicites, dangereux ou interdits à l'import/export sont refusés. SEZY peut demander l'ouverture d'un colis pour vérification avant acheminement.</p>
<h2>Délais &amp; responsabilité</h2>
<p>Les délais annoncés (3 à 5 jours) sont indicatifs et dépendent des vols disponibles. La responsabilité de SEZY s'applique dans les limites prévues pour chaque envoi.</p>
<h2>Paiement</h2>
<p>Les paiements s'effectuent via les moyens locaux proposés (Mobile Money, Wero, Lemfi). Toute commande est confirmée après réception du règlement convenu.</p>
<h2>Contact</h2>
<p>Pour toute question, contactez-nous via notre page <a href="/contact">contact</a>.</p>
HTML,
        ]);

        LegalDocument::updateOrCreate(['key' => 'privacy'], [
            'title' => 'Confidentialité',
            'is_published' => true,
            'body' => <<<'HTML'
<h2>Données collectées</h2>
<p>Nous collectons les informations nécessaires à nos services : nom, coordonnées, détails d'expédition. Elles ne sont jamais revendues à des tiers.</p>
<h2>Utilisation</h2>
<p>Vos données servent à traiter vos envois, vous tenir informé du suivi, et améliorer la qualité de nos services.</p>
<h2>Conservation</h2>
<p>Les données sont conservées le temps nécessaire au service et à nos obligations légales, puis supprimées ou anonymisées.</p>
<h2>Vos droits</h2>
<p>Vous pouvez demander l'accès, la rectification ou la suppression de vos données à tout moment via notre page <a href="/contact">contact</a>.</p>
HTML,
        ]);
    }

    public function down(): void
    {
        LegalDocument::whereIn('key', ['cgu', 'privacy'])->delete();
    }
};
