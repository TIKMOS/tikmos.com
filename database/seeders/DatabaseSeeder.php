<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Rôles/permissions + compte administrateur. Le contenu de démonstration
        // (services, réalisations, blog, boutique, avis, FAQ, équipe, partenaires)
        // est seedé par les migrations dédiées.
        $this->call([
            RolePermissionSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
