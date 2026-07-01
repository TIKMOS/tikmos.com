<?php

declare(strict_types=1);

use App\Models\TeamMember;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Remplace l'équipe de démo Sezy par l'équipe Tikmos.
        TeamMember::query()->delete();

        $members = [
            ['name' => 'Moïse TIKO', 'role' => 'Manager en chef', 'featured' => true],
            ['name' => 'Pesquidox AKODAZOUN', 'role' => 'Dev Web & Designer', 'featured' => false],
            ['name' => 'Carlos LEGBAGA', 'role' => 'Ingénieur réseaux', 'featured' => false],
        ];

        foreach ($members as $i => $m) {
            TeamMember::create([
                'name' => $m['name'],
                'role_title' => $m['role'],
                'position' => $i,
                'is_featured' => $m['featured'],
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        TeamMember::whereIn('name', ['Moïse TIKO', 'Pesquidox AKODAZOUN', 'Carlos LEGBAGA'])->delete();
    }
};
