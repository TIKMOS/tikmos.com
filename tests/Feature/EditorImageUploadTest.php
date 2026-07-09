<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

function editorStaff(): User
{
    $u = User::factory()->create(['status' => 'active']);
    $u->assignRole(Role::Admin->value);

    return $u;
}

it('téléverse une image inline et renvoie son URL', function (): void {
    $res = $this->actingAs(editorStaff())
        ->post('/admin/editor/image', ['file' => UploadedFile::fake()->image('photo.png', 60, 60)]);

    $res->assertOk()->assertJsonStructure(['url']);
    expect($res->json('url'))->toContain('/storage/editor/');

    // Le fichier est bien stocké sur le disque public.
    $path = str($res->json('url'))->after('/storage/')->value();
    Storage::disk('public')->assertExists($path);
});

it('refuse un fichier non-image', function (): void {
    $this->actingAs(editorStaff())
        ->post('/admin/editor/image', ['file' => UploadedFile::fake()->create('doc.pdf', 20, 'application/pdf')])
        ->assertSessionHasErrors('file');
});

it('refuse un visiteur non authentifié', function (): void {
    $this->post('/admin/editor/image', ['file' => UploadedFile::fake()->image('x.png')])
        ->assertRedirect('/connexion');
});

it('refuse un client (rôle non-staff)', function (): void {
    $client = User::factory()->create(['status' => 'active']);
    $client->assignRole(Role::Client->value);

    $this->actingAs($client)
        ->post('/admin/editor/image', ['file' => UploadedFile::fake()->image('x.png')])
        ->assertForbidden();
});
