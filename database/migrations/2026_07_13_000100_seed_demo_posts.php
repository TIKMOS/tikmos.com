<?php

declare(strict_types=1);

use App\Enums\Publication;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $cats = [];
        foreach (['Data & IA', 'Conseils', 'Actualités'] as $i => $name) {
            $cats[$name] = PostCategory::create(['name' => $name, 'slug' => Str::slug($name), 'position' => $i, 'is_published' => true]);
        }

        $posts = [
            ['Bien démarrer avec la data science en entreprise', 'Data & IA', 'Par où commencer pour tirer de la valeur de vos données, sans se perdre dans la technique.', 6],
            ['5 signes qu\'il est temps de digitaliser un processus', 'Conseils', 'Quand le tableur ne suffit plus : les signaux qui doivent vous alerter.', 4],
            ['L\'IA appliquée : mythes et réalités', 'Data & IA', "Ce que l'intelligence artificielle peut vraiment faire pour votre activité, aujourd'hui.", 7],
        ];

        foreach ($posts as $i => [$title, $cat, $excerpt, $rt]) {
            Post::create([
                'post_category_id' => $cats[$cat]->id,
                'slug' => Str::slug($title),
                'title' => $title,
                'excerpt' => $excerpt,
                'body' => '<p>'.$excerpt.'</p><p>Contenu à compléter depuis l\'administration.</p>',
                'reading_time' => $rt,
                'published_at' => now()->subDays(($i + 1) * 5),
                'status' => Publication::Published,
            ]);
        }
    }

    public function down(): void
    {
        Post::query()->forceDelete();
        PostCategory::query()->delete();
    }
};
