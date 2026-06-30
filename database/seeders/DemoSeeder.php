<?php

namespace Database\Seeders;

use App\Enums\ParcelStatus;
use App\Models\Flight;
use App\Models\Lead;
use App\Models\ParcelRequest;
use App\Models\User;
use App\Services\ParcelService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::firstOrCreate(
            ['email' => 'client@demo.test'],
            ['name' => 'Aïcha Koudous', 'password' => Hash::make('password'), 'status' => 'active', 'city' => 'Cotonou'],
        );
        $client->syncRoles(['client']);

        Flight::factory()->count(3)->create();

        $service = app(ParcelService::class);

        // Colis livré (timeline complète)
        $delivered = $service->create([
            'sender_id' => $client->id, 'recipient_name' => 'Marc Dossou', 'type' => 'vetements',
            'origin' => 'cotonou', 'destination' => 'paris', 'weight_kg' => 4, 'price' => 18000,
        ]);
        foreach ([ParcelStatus::InTransit, ParcelStatus::Arrived, ParcelStatus::ReadyForPickup, ParcelStatus::Delivered] as $status) {
            $service->advanceTo($delivered, $status, location: 'SEZY');
        }

        // Colis en transit
        $inTransit = $service->create([
            'sender_id' => $client->id, 'recipient_name' => 'Fatou Bello', 'type' => 'telephone',
            'origin' => 'paris', 'destination' => 'cotonou', 'weight_kg' => 1.2, 'price' => 9000,
        ]);
        $service->advanceTo($inTransit, ParcelStatus::InTransit, location: 'Vol Paris → Cotonou');

        // Colis enregistré
        $service->create([
            'sender_id' => $client->id, 'recipient_name' => 'Jean Koffi', 'type' => 'documents',
            'origin' => 'cotonou', 'destination' => 'paris', 'weight_kg' => 0.5, 'price' => 5000,
        ]);

        // Demandes & messages
        ParcelRequest::factory()->count(2)->create(['user_id' => $client->id, 'status' => 'new']);
        Lead::factory()->count(3)->create();
    }
}
