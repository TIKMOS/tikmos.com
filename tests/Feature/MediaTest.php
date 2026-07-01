<?php

use App\Enums\Role;
use App\Media\ImageService;
use App\Models\Product;
use App\Models\User;
use App\Settings\MediaSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('public');
});

function mediaProduct(string $name = 'Clavier'): Product
{
    return Product::create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.uniqid(),
        'price' => 5000,
        'is_active' => true,
    ]);
}

it('attache une image, génère les conversions WebP et persiste le texte alternatif', function (): void {
    $product = mediaProduct();
    $file = UploadedFile::fake()->image('photo.jpg', 1200, 900);

    $media = app(ImageService::class)->attach($product, $file, 'gallery', 'Clavier mécanique');

    expect($media->hasGeneratedConversion('thumb'))->toBeTrue()
        ->and($media->hasGeneratedConversion('card'))->toBeTrue()
        ->and($media->hasGeneratedConversion('full'))->toBeTrue()
        ->and(pathinfo($media->getUrl('card'), PATHINFO_EXTENSION))->toBe('webp')
        ->and($media->getCustomProperty('alt'))->toBe('Clavier mécanique');
});

it('dérive les règles de validation des réglages médias', function (): void {
    $s = app(MediaSettings::class);
    $s->max_size_kb = 1024;
    $s->accepted_formats = ['png', 'webp'];
    $s->gallery_max = 3;
    $s->save();

    $rules = app(ImageService::class)->rules('images');

    expect($rules['images'])->toContain('max:3')
        ->and($rules['images.*'])->toContain('mimes:png,webp')
        ->and($rules['images.*'])->toContain('max:1024');
});

it('réordonne les médias et définit la couverture', function (): void {
    $product = mediaProduct();
    $svc = app(ImageService::class);
    $a = $svc->attach($product, UploadedFile::fake()->image('a.jpg'), 'gallery');
    $b = $svc->attach($product, UploadedFile::fake()->image('b.jpg'), 'gallery');

    // b passe en couverture (position 1).
    $svc->reorder([$b->id, $a->id]);

    $ordered = $product->refresh()->getMedia('gallery')->pluck('id')->all();
    expect($ordered[0])->toBe($b->id);
});

it('purge les médias orphelins', function (): void {
    $product = mediaProduct();
    app(ImageService::class)->attach($product, UploadedFile::fake()->image('a.jpg'), 'gallery');

    expect(Media::count())->toBe(1);

    $product->forceDelete(); // media reste orpheline (pas de cascade forcée ici)

    $pruned = app(ImageService::class)->pruneOrphans();

    expect($pruned)->toBeGreaterThanOrEqual(0)
        ->and(Media::whereDoesntHave('model')->count())->toBe(0);
});

it('la commande media:prune s\'exécute', function (): void {
    $this->artisan('media:prune')->assertSuccessful();
});

it('x-tk.image rend un srcset responsive et le lazy-load', function (): void {
    $product = mediaProduct();
    $media = app(ImageService::class)->attach($product, UploadedFile::fake()->image('a.jpg', 1000, 1000), 'gallery', 'Alt test');

    $html = Blade::render('<x-tk.image :media="$m" sizes="50vw" />', ['m' => $media]);

    expect($html)->toContain('srcset=')
        ->and($html)->toContain('800w')
        ->and($html)->toContain('loading="lazy"')
        ->and($html)->toContain('Alt test');
});

it('x-tk.image passe le hero en priorité (eager + fetchpriority)', function (): void {
    $product = mediaProduct();
    $media = app(ImageService::class)->attach($product, UploadedFile::fake()->image('a.jpg'), 'gallery');

    $html = Blade::render('<x-tk.image :media="$m" :priority="true" />', ['m' => $media]);

    expect($html)->toContain('loading="eager"')
        ->and($html)->toContain('fetchpriority="high"');
});

it('téléverse des images depuis l\'admin produit (galerie)', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Volt::actingAs($admin)->test('pages.admin.boutique.products')
        ->call('create')
        ->set('name', 'Souris Pro')
        ->set('price', 9000)
        ->set('images', [UploadedFile::fake()->image('m.jpg', 900, 900)])
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Souris Pro')->first();
    expect($product)->not->toBeNull()
        ->and($product->getMedia('gallery'))->toHaveCount(1);
});

it('refuse un fichier non-image dans la galerie admin', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    Volt::actingAs($admin)->test('pages.admin.boutique.products')
        ->call('create')
        ->set('name', 'Mauvais fichier')
        ->set('price', 1000)
        ->set('images', [UploadedFile::fake()->create('malware.pdf', 100, 'application/pdf')])
        ->call('save')
        ->assertHasErrors('images.*');
});
