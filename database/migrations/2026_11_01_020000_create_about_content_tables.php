<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('about_paragraphs')) {
            Schema::create('about_paragraphs', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 100)->unique();
                $table->text('body');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
                $table->index('sort_order');
            });
        }

        if (! Schema::hasTable('about_values')) {
            Schema::create('about_values', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('title', 100);
                $table->string('description', 500);
                $table->string('icon', 40)->default('target');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
                $table->index(['is_visible', 'sort_order']);
            });
        }

        $now = now();
        DB::table('about_paragraphs')->insertOrIgnore([
            ['key' => 'parcours-1', 'body' => "Je suis Moïse TIKO, alias de Tikmos. Tout commence par les mathématiques : la rigueur, la preuve, l'art de décomposer un problème complexe en étapes claires. Cet état d'esprit, je l'ai porté de l'enseignement vers l'ingénierie data & IA.", 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'parcours-2', 'body' => "Aujourd'hui, je conçois et pilote des solutions informatiques sur mesure, je forme les équipes, et j'accompagne entreprises comme particuliers — installation, gestion administrative, orientation. En parallèle, je sélectionne des chaussures de luxe pour celles et ceux qui aiment l'exigence du beau.", 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'parcours-3', 'body' => 'Et je ne travaille jamais seul : une équipe de confiance m’épaule sur chaque projet. Ma promesse : comprendre votre besoin, et le livrer.', 'sort_order' => 3, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('about_values')->insertOrIgnore([
            ['key' => 'rigueur', 'title' => 'Rigueur', 'description' => 'La précision des mathématiques appliquée à chaque livrable.', 'icon' => 'target', 'sort_order' => 1, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'pedagogie', 'title' => 'Pédagogie', 'description' => 'Rendre simple ce qui est complexe — même pour les non-informaticiens.', 'icon' => 'lightbulb', 'sort_order' => 2, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'execution', 'title' => 'Exécution', 'description' => "On ne s'arrête pas à l'idée : on livre, on forme, on accompagne.", 'icon' => 'rocket', 'sort_order' => 3, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'exigence', 'title' => 'Exigence', 'description' => "L'exigence du luxe, dans le service comme dans les produits.", 'icon' => 'gem', 'sort_order' => 4, 'is_visible' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    // Content entered by an administrator must be backed up before any manual schema rollback.
    public function down(): void {}
};
