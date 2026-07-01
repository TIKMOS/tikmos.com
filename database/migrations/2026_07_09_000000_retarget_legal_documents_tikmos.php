<?php

declare(strict_types=1);

use App\Models\LegalDocument;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        LegalDocument::updateOrCreate(['key' => 'cgu'], [
            'title' => "Conditions générales d'utilisation",
            'is_published' => true,
            'body' => <<<'HTML'
<h2>Objet</h2>
<p>Les présentes conditions encadrent l'utilisation de la plateforme Tikmos : présentation des services (formation, conception de solutions informatiques, installation, assistance, orientation), demandes de devis, et boutique de chaussures de luxe.</p>
<h2>Compte & espace client</h2>
<p>La création d'un compte permet de suivre ses demandes, devis et commandes. Vous êtes responsable de la confidentialité de vos identifiants.</p>
<h2>Devis & prestations</h2>
<p>Les devis sont établis sur la base des informations communiquées. Une prestation démarre après acceptation du devis. Les délais annoncés sont indicatifs.</p>
<h2>Propriété intellectuelle</h2>
<p>Les contenus de la plateforme sont protégés. Toute reproduction sans autorisation est interdite.</p>
<h2>Contact</h2>
<p>Pour toute question, contactez-nous via notre page <a href="/contact">contact</a>.</p>
HTML,
        ]);

        LegalDocument::updateOrCreate(['key' => 'cgv'], [
            'title' => 'Conditions générales de vente',
            'is_published' => true,
            'body' => <<<'HTML'
<h2>Champ d'application</h2>
<p>Les présentes CGV régissent les ventes de la boutique Tikmos (chaussures de luxe).</p>
<h2>Commande</h2>
<p>La commande se fait en ligne ou via WhatsApp. Elle est confirmée par Tikmos avant préparation. Les tailles disponibles sont indiquées sur chaque fiche produit.</p>
<h2>Prix & paiement</h2>
<p>Les prix sont indiqués en francs CFA (XOF). Le <strong>paiement s'effectue à la livraison</strong> (espèces ou mobile money à la remise), sauf autre modalité convenue.</p>
<h2>Livraison</h2>
<p>Les délais et frais de livraison sont communiqués lors de la confirmation. Vous suivez l'état de votre commande dans votre espace client.</p>
<h2>Rétractation & échanges</h2>
<p>Les conditions d'échange ou de retour sont convenues au cas par cas ; contactez-nous rapidement en cas de problème.</p>
HTML,
        ]);

        LegalDocument::updateOrCreate(['key' => 'privacy'], [
            'title' => 'Confidentialité',
            'is_published' => true,
            'body' => <<<'HTML'
<h2>Données collectées</h2>
<p>Nous collectons les informations nécessaires à nos services : nom, coordonnées, détails de vos demandes et commandes. Elles ne sont jamais revendues à des tiers.</p>
<h2>Utilisation</h2>
<p>Vos données servent à traiter vos demandes de devis et commandes, vous tenir informé, et améliorer la qualité de nos services.</p>
<h2>Conservation</h2>
<p>Les données sont conservées le temps nécessaire au service et à nos obligations légales, puis supprimées ou anonymisées.</p>
<h2>Vos droits</h2>
<p>Vous pouvez à tout moment <strong>exporter</strong> ou demander la <strong>suppression</strong> de vos données depuis votre espace client (rubrique « Mon compte »), ou via notre page <a href="/contact">contact</a>.</p>
HTML,
        ]);
    }

    public function down(): void
    {
        LegalDocument::where('key', 'cgv')->delete();
    }
};
