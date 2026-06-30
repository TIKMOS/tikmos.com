<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $services = [
            ['category' => ServiceCategory::Formation, 'title' => 'Formation Data Science et IA', 'excerpt' => "Initier ou perfectionner vos équipes à la data et à l'intelligence artificielle appliquée.", 'price_label' => 'Sur devis'],
            ['category' => ServiceCategory::Formation, 'title' => 'Formation suites logicielles', 'excerpt' => 'Bureautique et logiciels métiers : monter en compétence, concrètement.', 'price_label' => 'Sur devis'],
            ['category' => ServiceCategory::Conception, 'title' => 'Conception de solutions sur mesure', 'excerpt' => 'Applications métier, plateformes, automatisation et projets data/IA, du cadrage à la livraison.', 'price_label' => 'Sur devis'],
            ['category' => ServiceCategory::Installation, 'title' => 'Installation informatique', 'excerpt' => 'Postes, réseaux et déploiement de logiciels, proprement installés et configurés.', 'price_label' => 'Sur devis'],
            ['category' => ServiceCategory::Assistance, 'title' => 'Assistance en gestion administrative', 'excerpt' => 'Un appui fiable pour organiser et fluidifier votre gestion au quotidien.', 'price_label' => 'Sur devis'],
            ['category' => ServiceCategory::Orientation, 'title' => 'Orientation professionnelle', 'excerpt' => 'Conseil et accompagnement pour construire un parcours qui vous ressemble.', 'price_label' => 'Sur devis'],
        ];

        foreach ($services as $i => $data) {
            Service::query()->create([
                ...$data,
                'slug' => Str::slug($data['title']),
                'body' => '<p>'.$data['excerpt'].'</p>',
                'position' => $i,
                'status' => Publication::Published,
            ]);
        }
    }

    public function down(): void
    {
        Service::query()->forceDelete();
    }
};
