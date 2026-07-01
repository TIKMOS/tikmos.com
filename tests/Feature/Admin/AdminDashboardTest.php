<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\Role;
use App\Enums\ServiceRequestStatus;
use App\Models\Order;
use App\Models\Quote;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function boDashAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('affiche le tableau de bord admin avec les KPIs Tikmos', function (): void {
    $this->actingAs(boDashAdmin())->get('/admin')
        ->assertOk()
        ->assertSee('Tableau de bord')
        ->assertSee('Demandes ce mois')
        ->assertSee('CA estimé');
});

it('calcule le CA estimé (devis acceptés + commandes livrées)', function (): void {
    $request = ServiceRequest::factory()->status(ServiceRequestStatus::Accepted)->create();
    Quote::create([
        'service_request_id' => $request->id, 'reference' => 'TKM-Q-CA0001',
        'amount' => 750000, 'currency' => 'XOF', 'status' => QuoteStatus::Accepted,
    ]);
    Order::create([
        'number' => 'TKM-CMD-2026-00001', 'user_id' => User::factory()->create()->id,
        'status' => OrderStatus::Delivered,
        'payment_method' => 'cod', 'payment_status' => 'paid',
        'subtotal' => 250000, 'total' => 250000,
        'contact_name' => 'X', 'contact_phone' => 'Y', 'delivery_address' => 'Z',
    ]);

    Volt::actingAs(boDashAdmin())->test('pages.admin.dashboard')
        ->assertViewHas('revenue', 1000000)
        ->assertSee('1 000 000', false);
});

it('signale les nouvelles demandes à qualifier', function (): void {
    ServiceRequest::factory()->status(ServiceRequestStatus::Sent)->count(2)->create();

    Volt::actingAs(boDashAdmin())->test('pages.admin.dashboard')
        ->assertViewHas('newRequests', 2)
        ->assertSee('nouvelle(s) demande(s)');
});
