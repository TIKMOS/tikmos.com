<?php

use App\Enums\Role;
use App\Models\User;
use App\Settings\AboutSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('allows a permitted administrator to replace the About page image', function (): void {
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $this->actingAs($admin)->get('/admin/reglages')->assertOk()->assertSee('À propos');
    $this->actingAs($admin)->get('/admin/reglages/a-propos')->assertOk()->assertSee('Photo de présentation');

    Volt::actingAs($admin)
        ->test('pages.admin.reglages.about')
        ->set('imageFile', UploadedFile::fake()->image('portrait.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $settings = app(AboutSettings::class);

    expect($settings->image)->not->toBeNull();
    Storage::disk('public')->assertExists($settings->image);
    $this->get('/a-propos')
        ->assertOk()
        ->assertSee(Storage::disk('public')->url($settings->image), false)
        ->assertDontSee('img/about-illustration.svg');
});

it('restores the default illustration when the administrator removes the custom image', function (): void {
    Storage::fake('public');
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);
    $settings = app(AboutSettings::class);
    $settings->image = UploadedFile::fake()->image('portrait.jpg')->store('about', 'public');
    $settings->save();
    $oldImage = $settings->image;

    Volt::actingAs($admin)
        ->test('pages.admin.reglages.about')
        ->call('removeImage')
        ->assertHasNoErrors();

    expect(app(AboutSettings::class)->image)->toBeNull();
    Storage::disk('public')->assertMissing($oldImage);
    $this->get('/a-propos')->assertOk()->assertSee('img/about-illustration.svg');
});

it('keeps the About image editor restricted to authorized staff', function (): void {
    $support = User::factory()->create();
    $support->assignRole(Role::Support->value);

    $this->actingAs($support)->get('/admin/reglages/a-propos')->assertForbidden();
});
