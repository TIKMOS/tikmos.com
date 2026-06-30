<?php

use App\Models\Partner;
use App\Models\TeamMember;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $members = [
            ['Boris Vital', 'Fondateur & CEO', 'Le moteur de SEZY. Toujours entre deux messages WhatsApp pour que vos colis arrivent à bon port.', true, ['Carburant : café', 'Cotonou ↔ Paris'], [['network' => 'linkedin', 'url' => '#']]],
            ['Awa Sodjavi', 'Responsable Cotonou', 'Gère la réception et la distribution côté Bénin avec une précision redoutable.', false, ['Reine de la logistique', 'Team piment'], [['network' => 'instagram', 'url' => '#']]],
            ['Karim Bello', 'Relais Paris', 'Votre contact en France : collecte, réexpédition, et le sourire en prime.', false, ['Vélo only', 'Toujours en musique'], [['network' => 'linkedin', 'url' => '#']]],
            ['Nadia Koffi', 'Support client', 'Répond à vos questions à toute heure. La voix rassurante de SEZY.', false, ['Réponse en 2 min', 'Team chat'], []],
            ['Stéphane Dossou', 'Conseiller mobilité', 'Accompagne les projets d\'études et de mobilité, étape par étape.', false, ['Campus France pro', 'Lecteur compulsif'], []],
        ];

        foreach ($members as $i => [$name, $role, $bio, $featured, $facts, $socials]) {
            TeamMember::create([
                'name' => $name,
                'role_title' => $role,
                'bio' => $bio,
                'funfacts' => $facts,
                'socials' => $socials,
                'is_featured' => $featured,
                'is_active' => true,
                'position' => $i,
            ]);
        }

        foreach (['Air France', 'Asky Airlines', 'MoMo', 'Wero', 'Lemfi'] as $i => $name) {
            Partner::create(['name' => $name, 'position' => $i, 'is_active' => true]);
        }
    }

    public function down(): void
    {
        TeamMember::query()->delete();
        Partner::query()->delete();
    }
};
