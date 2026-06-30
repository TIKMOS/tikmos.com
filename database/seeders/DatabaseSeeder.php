<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminSeeder::class,
            // Contenu de démonstration de base (admin pesquilou + clients + colis + commande).
            // Exécuté aussi en production pour la présentation client.
            ShowcaseSeeder::class,
        ]);

        // Jeu de démo additionnel uniquement en local (client@demo.test, vols, leads).
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
