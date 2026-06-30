<?php

use App\Models\Review;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $reviews = [
            ['Aïcha K.', 'Paris', 5, 'Colis envoyé un mardi, récupéré à Paris le jeudi. Impeccable, je recommande !'],
            ['Marc D.', 'Bruxelles', 5, 'Enfin un service sérieux pour envoyer à ma famille à Cotonou. Suivi parfait.'],
            ['Fatou B.', 'Cotonou', 5, 'Ils ont acheté ma commande Zara et tout est arrivé nickel. Service au top.'],
            ['Joël A.', 'Paris', 4, 'Suivi clair, équipe très réactive sur WhatsApp. Je recommande à 100%.'],
            ['Sandra M.', 'Lyon', 5, 'Première fois que j\'envoie un colis aussi sereinement. Merci SEZY !'],
            ['Kévin O.', 'Cotonou', 5, 'Rapide, fiable et abordable. Le paiement en Mobile Money est super pratique.'],
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
        Review::where('source', 'manual')->where('is_featured', true)->delete();
    }
};
