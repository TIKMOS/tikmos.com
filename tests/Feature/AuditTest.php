<?php

use App\Audit\ActivityFeed;
use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function auditAdmin(): User
{
    $u = User::factory()->create();
    $u->assignRole(Role::Admin->value);

    return $u;
}

function auditClient(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Client->value);

    return $u;
}

it('journalise la création avec auteur, domaine, libellé et contexte', function (): void {
    $admin = auditAdmin();
    $this->actingAs($admin);

    $product = Product::create(['name' => 'Écran 4K', 'slug' => 'ecran-4k', 'price' => 50000, 'is_active' => true]);

    $activity = Activity::where('subject_type', Product::class)->where('subject_id', $product->id)->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->event)->toBe('created')
        ->and($activity->log_name)->toBe('boutique')
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->properties['subject_label'])->toBe('Écran 4K')
        ->and($activity->properties['context'])->toHaveKey('channel');
});

it('journalise un diff avant/après sur les champs surveillés', function (): void {
    $this->actingAs(auditAdmin());
    $product = Product::create(['name' => 'Clavier', 'slug' => 'clavier', 'price' => 20000, 'is_active' => true]);

    $product->update(['price' => 25000]);

    $activity = Activity::where('subject_type', Product::class)->where('subject_id', $product->id)
        ->where('event', 'updated')->latest()->first();

    expect($activity->properties['attributes']['price'])->toBe(25000)
        ->and($activity->properties['old']['price'])->toBe(20000);
});

it('ignore les modifications de champs non surveillés', function (): void {
    $this->actingAs(auditAdmin());
    Product::create(['name' => 'Souris', 'slug' => 'souris', 'price' => 8000, 'is_active' => true, 'is_featured' => false]);
    $product = Product::firstWhere('slug', 'souris'); // rechargé depuis la base (attributs hydratés)

    $before = Activity::count();
    $product->update(['slug' => 'souris-pro']); // slug hors périmètre d'audit

    expect(Activity::count())->toBe($before);
});

it('agrège une timeline par acteur', function (): void {
    $admin = auditAdmin();
    $this->actingAs($admin);
    Product::create(['name' => 'Casque', 'slug' => 'casque', 'price' => 30000, 'is_active' => true]);

    $timeline = app(ActivityFeed::class)->timelineFor($admin);

    expect($timeline)->not->toBeEmpty()
        ->and($timeline->first()['actor'])->toBe($admin->name)
        ->and($timeline->first()['source'])->toBe('activity');
});

it('affiche le journal filtré dans l\'admin', function (): void {
    $admin = auditAdmin();
    $this->actingAs($admin);
    Product::create(['name' => 'Webcam', 'slug' => 'webcam', 'price' => 40000, 'is_active' => true]);

    Volt::test('pages.admin.audit')
        ->set('logName', 'boutique')
        ->assertSee('Webcam')
        ->set('logName', 'paiement')
        ->assertDontSee('Webcam');
});

it('refuse l\'audit sans la permission', function (): void {
    $this->actingAs(auditClient())
        ->get('/admin/audit')
        ->assertForbidden();
});

it('purge le journal au-delà de la rétention', function (): void {
    $this->artisan('audit:prune', ['--days' => 30])->assertSuccessful();
});
