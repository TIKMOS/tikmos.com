<?php

use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function spaceClient(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Client->value);

    return $u;
}

it('charge les pages de l\'espace client (liste + détail) sans erreur', function (): void {
    $client = spaceClient();

    $order = Order::create([
        'number' => 'TKM-CMD-2026-90001', 'user_id' => $client->id, 'status' => OrderStatus::Delivered,
        'subtotal' => 8000, 'total' => 8000, 'payment_method' => 'cod', 'payment_status' => 'unpaid',
        'contact_name' => 'X', 'contact_phone' => 'Y', 'delivery_address' => 'Z',
    ]);

    $request = ServiceRequest::create([
        'reference' => 'TKM-DEV-2026-90001', 'user_id' => $client->id, 'name' => 'Client', 'email' => $client->email,
        'phone' => '+22990000000', 'subject' => 'Besoin', 'message' => 'Bonjour', 'status' => 'sent',
    ]);

    $this->actingAs($client);
    $this->get('/app')->assertOk();
    $this->get('/app/achats')->assertOk();
    $this->get('/app/achats/'.$order->number)->assertOk()->assertSee($order->number);
    $this->get('/app/devis')->assertOk();
    $this->get('/app/devis/'.$request->reference)->assertOk()->assertSee($request->reference);
    $this->get('/app/compte')->assertOk();
});

it('charge la fiche devis admin (détail par référence)', function (): void {
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole(Role::SuperAdmin->value);

    $request = ServiceRequest::create([
        'reference' => 'TKM-DEV-2026-90002', 'name' => 'Prospect', 'email' => 'p@example.com',
        'phone' => '+22990000000', 'subject' => 'Projet', 'message' => 'Détails', 'status' => 'sent',
    ]);

    $this->actingAs($admin)->get('/admin/devis/'.$request->reference)->assertOk()->assertSee($request->reference);
});
