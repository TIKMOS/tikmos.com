<?php

namespace Database\Seeders;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\ParcelStatus;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ParcelService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Contenu de démonstration de base (présentation client) : un administrateur dédié,
 * quelques clients, des colis et une commande boutique. Idempotent.
 */
class ShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Administrateur (propriétaire de la démo) ---
        $admin = User::firstOrCreate(
            ['email' => 'pesquilou@gmail.com'],
            [
                'name' => 'Pesquidox',
                'password' => Hash::make(env('SHOWCASE_ADMIN_PASSWORD', 'password')),
                'status' => 'active',
                'city' => 'Cotonou',
                'country' => 'BJ',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles([Role::SuperAdmin->value]);

        // --- Clients de démo ---
        $clients = [
            ['Aïcha Koudous', 'aicha@demo.test', '+229 01 66 00 00 01', 'Cotonou'],
            ['Marc Dossou', 'marc@demo.test', '+33 6 12 34 56 78', 'Paris'],
            ['Fatou Bello', 'fatou@demo.test', '+229 01 66 00 00 02', 'Calavi'],
            ['Joël Adjovi', 'joel@demo.test', '+32 470 11 22 33', 'Bruxelles'],
        ];

        $first = null;
        foreach ($clients as $i => [$name, $email, $phone, $city]) {
            $client = User::firstOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password'), 'status' => 'active', 'phone' => $phone, 'city' => $city],
            );
            $client->syncRoles([Role::Client->value]);
            $first ??= $client;
        }

        // --- Colis de démo pour le premier client (timeline variée) ---
        if ($first && $first->parcels()->count() === 0) {
            $service = app(ParcelService::class);

            $delivered = $service->create([
                'sender_id' => $first->id, 'recipient_name' => 'Marc Dossou', 'type' => 'vetements',
                'origin' => 'cotonou', 'destination' => 'paris', 'weight_kg' => 4, 'price' => 18000,
            ]);
            foreach ([ParcelStatus::InTransit, ParcelStatus::Arrived, ParcelStatus::ReadyForPickup, ParcelStatus::Delivered] as $status) {
                $service->advanceTo($delivered, $status, location: 'SEZY');
            }

            $inTransit = $service->create([
                'sender_id' => $first->id, 'recipient_name' => 'Fatou Bello', 'type' => 'telephone',
                'origin' => 'paris', 'destination' => 'cotonou', 'weight_kg' => 1.2, 'price' => 9000,
            ]);
            $service->advanceTo($inTransit, ParcelStatus::InTransit, location: 'Vol Paris → Cotonou');

            $service->create([
                'sender_id' => $first->id, 'recipient_name' => 'Jean Koffi', 'type' => 'documents',
                'origin' => 'cotonou', 'destination' => 'paris', 'weight_kg' => 0.5, 'price' => 5000,
            ]);
        }

        // --- Commande boutique de démo ---
        if ($first && Order::count() === 0) {
            $products = Product::active()->take(2)->get();
            if ($products->isNotEmpty()) {
                $subtotal = (int) $products->sum('price');
                $order = Order::create([
                    'number' => 'SZ-CMD-'.now()->year.'-00001',
                    'user_id' => $first->id,
                    'status' => OrderStatus::Confirmed,
                    'payment_method' => PaymentMethod::Cod,
                    'payment_status' => OrderPaymentStatus::Unpaid,
                    'subtotal' => $subtotal,
                    'delivery_fee' => 0,
                    'total' => $subtotal,
                    'contact_name' => $first->name,
                    'contact_phone' => (string) $first->phone,
                    'delivery_address' => 'Agontinkon, étoile rouge',
                    'delivery_city' => 'Cotonou',
                    'confirmed_at' => now(),
                ]);
                foreach ($products as $p) {
                    $order->items()->create([
                        'product_id' => $p->id, 'name' => $p->name,
                        'unit_price' => $p->price, 'quantity' => 1, 'line_total' => $p->price,
                    ]);
                }
            }
        }
    }
}
