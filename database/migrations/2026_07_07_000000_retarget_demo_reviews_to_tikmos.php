<?php

declare(strict_types=1);

use App\Models\FaqItem;
use App\Models\Review;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // --- FAQ : remplace les questions de démo Sezy (colis) par des questions Tikmos. ---
        FaqItem::query()->delete();
        $faqs = [
            ['Comment demander un devis ?', '<p>Choisissez un service, décrivez votre besoin depuis la fiche, et vous recevez un devis clair. Vous suivez tout dans votre espace client.</p>', 'Services'],
            ['Proposez-vous des formations sur mesure ?', '<p>Oui. Nos formations (data, IA, suites logicielles) sont adaptées à votre équipe et à vos objectifs.</p>', 'Services'],
            ['Comment se passe le paiement en boutique ?', '<p>Le paiement se fait <strong>à la livraison</strong>. Vous commandez en ligne ou via WhatsApp, et réglez à la remise de votre commande.</p>', 'Boutique'],
            ['Comment choisir ma pointure ?', '<p>Chaque fiche produit indique les tailles disponibles. Sélectionnez votre pointure avant d\'ajouter au panier.</p>', 'Boutique'],
            ['Intervenez-vous en dehors de ma ville ?', '<p>Oui, nous accompagnons des clients dans plusieurs villes. Contactez-nous pour en discuter.</p>', 'Général'],
        ];
        foreach ($faqs as $i => [$q, $a, $cat]) {
            FaqItem::create(['question' => $q, 'answer' => $a, 'category' => $cat, 'position' => $i, 'is_published' => true]);
        }

        // --- Avis : remplace les avis de démo Sezy (colis) par des témoignages Tikmos. ---
        Review::query()->where('source', 'manual')->delete();

        $reviews = [
            ['Awa D.', 'Cotonou', 5, 'Formation data/IA très concrète pour mon équipe. Pédagogie limpide, résultats immédiats.'],
            ['Marc T.', 'Abidjan', 5, 'Solution informatique livrée dans les délais et parfaitement adaptée à notre métier.'],
            ['Fatou B.', 'Paris', 5, 'Accompagnement sérieux et humain. On sent la rigueur derrière chaque livrable.'],
            ['Joël A.', 'Lomé', 4, 'Installation impeccable et conseils précieux. Équipe réactive et professionnelle.'],
            ['Sandra M.', 'Cotonou', 5, 'Des chaussures magnifiques, commande simple et suivie. La qualité est au rendez-vous.'],
            ['Kévin O.', 'Dakar', 5, "Orientation professionnelle qui m'a vraiment aidé à y voir clair. Merci !"],
        ];

        foreach ($reviews as $i => [$name, $city, $rating, $body]) {
            Review::create([
                'author_name' => $name,
                'author_city' => $city,
                'rating' => $rating,
                'body' => $body,
                'source' => 'manual',
                'status' => 'approved',
                'is_featured' => true,
                'published_at' => now()->subDays(($i + 1) * 3),
            ]);
        }
    }

    public function down(): void
    {
        // Reciblage de données de démo — non réversible.
    }
};
