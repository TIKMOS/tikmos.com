<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $projects = [
            ['title' => 'Plateforme data décisionnelle', 'category' => 'Data / IA', 'excerpt' => "Tableaux de bord et modèles prédictifs pour piloter l'activité en temps réel.", 'results' => 'Décisions accélérées, reporting automatisé.', 'year' => 2025],
            ['title' => 'Application métier sur mesure', 'category' => 'Application métier', 'excerpt' => 'Digitalisation complète d\'un processus métier, du devis à la facturation.', 'results' => 'Gain de temps significatif sur les opérations.', 'year' => 2024],
            ['title' => 'Programme de formation IA en entreprise', 'category' => 'Formation', 'excerpt' => "Montée en compétence d'équipes sur la data et l'IA appliquée.", 'results' => 'Équipes autonomes sur leurs cas d\'usage.', 'year' => 2026],
        ];

        foreach ($projects as $i => $data) {
            Project::query()->create([
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
        Project::query()->forceDelete();
    }
};
