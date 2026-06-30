<?php

use App\Models\FaqItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $items = [
            ['Combien de temps pour recevoir mon colis ?', "Comptez 3 à 5 jours ouvrés après le vol, souvent dès le lendemain s'il n'y a pas de retard."],
            ['Comment payer mes achats et envois ?', 'En local et en toute sécurité : Mobile Money (MoMo), Wero, Lemfi. Envoyez-nous simplement le lien du produit.'],
            ['Mes colis sont-ils assurés ?', "Oui, l'assurance est incluse : vos colis voyagent l'esprit tranquille."],
            ['Puis-je suivre mon colis en temps réel ?', 'Bien sûr. Chaque colis a une référence SZ-… que vous suivez sur le site ou depuis votre espace client.'],
            ["Vous expédiez vers d'autres pays ?", "Oui : depuis Paris, nous réexpédions vers l'UE (Belgique, Italie…), le Royaume-Uni et les États-Unis."],
        ];

        foreach ($items as $i => [$q, $a]) {
            FaqItem::create([
                'question' => $q,
                'answer' => '<p>'.$a.'</p>',
                'category' => 'Général',
                'position' => $i,
                'is_published' => true,
            ]);
        }
    }

    public function down(): void
    {
        FaqItem::query()->delete();
    }
};
