<?php

use App\Models\LegalDocument;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        LegalDocument::updateOrCreate(['key' => 'cookies'], [
            'title' => 'Politique cookies',
            'is_published' => true,
            'body' => <<<'HTML'
<h2>Qu'est-ce qu'un cookie ?</h2>
<p>Un cookie est un petit fichier déposé sur votre appareil lors de la visite d'un site. Il permet d'assurer le bon fonctionnement du site et, avec votre accord, d'en mesurer l'audience.</p>
<h2>Cookies nécessaires</h2>
<p>Indispensables au fonctionnement du site (session, sécurité, panier). Ils ne nécessitent pas votre consentement et sont toujours actifs.</p>
<h2>Mesure d'audience</h2>
<p>Avec votre accord, des statistiques anonymes nous aident à améliorer le site. Aucun cookie de mesure n'est déposé tant que vous ne l'avez pas accepté.</p>
<h2>Marketing</h2>
<p>Le cas échéant, des cookies de personnalisation peuvent être utilisés uniquement après votre consentement explicite.</p>
<h2>Gérer votre consentement</h2>
<p>Vous pouvez accepter, refuser ou personnaliser vos choix à tout moment via le lien « Gérer les cookies » en bas de page. Votre choix est conservé 6 mois.</p>
HTML,
        ]);
    }

    public function down(): void
    {
        LegalDocument::where('key', 'cookies')->delete();
    }
};
