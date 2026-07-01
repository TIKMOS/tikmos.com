<?php

use App\Enums\Role;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('modère et met en avant un avis', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $review = Review::create(['author_name' => 'Test', 'rating' => 5, 'body' => 'Super', 'source' => 'manual', 'status' => 'pending']);

    Volt::actingAs($admin)->test('pages.admin.avis')
        ->call('approve', $review)
        ->call('toggleFeatured', $review);

    $review->refresh();
    expect($review->status->value)->toBe('approved')
        ->and($review->is_featured)->toBeTrue();
});

it('affiche les avis approuvés sur la page témoignages', function (): void {
    Review::create(['author_name' => 'Client Test 88', 'rating' => 5, 'body' => 'Avis approuvé unique 88', 'source' => 'manual', 'status' => 'approved', 'is_featured' => true, 'published_at' => now()]);

    $this->get('/temoignages')->assertOk()->assertSee('Avis approuvé unique 88');
});
