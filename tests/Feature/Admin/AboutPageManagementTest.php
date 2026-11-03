<?php

use App\Enums\Role;
use App\Models\AboutParagraph;
use App\Models\AboutValue;
use App\Models\User;
use App\Settings\AboutSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function aboutManager(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    return $admin;
}

it('seeds the existing public copy and values without replacing the photo setting', function (): void {
    $settings = app(AboutSettings::class);
    $paragraphs = AboutParagraph::query()->orderBy('sort_order')->get();
    $values = AboutValue::query()->orderBy('sort_order')->get();

    expect($settings->image)->toBeNull()
        ->and($settings->title)->toBe("L'expertise qui exécute.")
        ->and($paragraphs)->toHaveCount(3)
        ->and($paragraphs->first()->body)->toContain('Moïse TIKO')
        ->and($values->pluck('title')->all())->toBe(['Rigueur', 'Pédagogie', 'Exécution', 'Exigence']);
});

it('allows an administrator to edit, order, hide, and restore about content', function (): void {
    $admin = aboutManager();
    $firstValue = AboutValue::query()->where('key', 'rigueur')->firstOrFail();
    $firstParagraph = AboutParagraph::query()->orderBy('sort_order')->firstOrFail();

    Volt::actingAs($admin)->test('pages.admin.reglages.about')
        ->set('title', 'Une expertise au service de vos projets.')
        ->set('paragraphs.0.body', 'Texte administré et conservé.')
        ->set('paragraphs.0.sort_order', 4)
        ->set('values.0.title', 'Méthode')
        ->set('values.0.sort_order', 8)
        ->set('values.0.is_visible', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(app(AboutSettings::class)->title)->toBe('Une expertise au service de vos projets.')
        ->and($firstParagraph->fresh()->body)->toBe('Texte administré et conservé.')
        ->and($firstValue->fresh()->title)->toBe('Méthode')
        ->and($firstValue->fresh()->is_visible)->toBeFalse();

    $this->get('/a-propos')->assertOk()->assertSee('Une expertise au service de vos projets.')->assertDontSee('Méthode');

    Volt::actingAs($admin)->test('pages.admin.reglages.about')
        ->set('values.3.is_visible', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->get('/a-propos')->assertSee('Méthode');
});

it('supports adding and removing biography paragraphs and adding values', function (): void {
    $admin = aboutManager();
    $component = Volt::actingAs($admin)->test('pages.admin.reglages.about')
        ->call('addParagraph')
        ->set('paragraphs.3.body', 'Un nouveau paragraphe validé.')
        ->set('paragraphs.3.sort_order', 4)
        ->call('addValue')
        ->set('values.4.title', 'Transparence')
        ->set('values.4.description', 'Une relation claire à chaque étape.')
        ->set('values.4.icon', 'check')
        ->set('values.4.sort_order', 5)
        ->set('values.4.is_visible', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(AboutParagraph::query()->where('body', 'Un nouveau paragraphe validé.')->exists())->toBeTrue()
        ->and(AboutValue::query()->where('title', 'Transparence')->exists())->toBeTrue();

    $component->call('removeParagraph', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect(AboutParagraph::query()->where('body', 'Un nouveau paragraphe validé.')->exists())->toBeFalse();
});

it('rejects unsafe CTA destinations and keeps the saved public content', function (): void {
    $admin = aboutManager();

    Volt::actingAs($admin)->test('pages.admin.reglages.about')
        ->set('quote_url', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['quote_url']);

    expect(app(AboutSettings::class)->quote_url)->toBe('/demander-un-devis');
});

it('renders the editable SEO metadata with the canonical About URL', function (): void {
    $settings = app(AboutSettings::class);
    $settings->seo_title = 'Profil professionnel de Moïse TIKO';
    $settings->seo_description = 'Parcours, expertise et valeurs de Moïse TIKO.';
    $settings->save();

    $this->get('/a-propos')->assertOk()
        ->assertSee('<title>Profil professionnel de Moïse TIKO</title>', false)
        ->assertSee('name="description" content="Parcours, expertise et valeurs de Moïse TIKO."', false)
        ->assertSee('rel="canonical" href="'.route('about').'"', false)
        ->assertSee('"@type":"ProfilePage"', false);
});

it('escapes public text and keeps the About editor protected by its existing permission', function (): void {
    $support = User::factory()->create();
    $support->assignRole(Role::Support->value);

    $this->actingAs($support)->get('/admin/reglages/a-propos')->assertForbidden();

    $component = Volt::actingAs(aboutManager())->test('pages.admin.reglages.about');
    Auth::login($support);
    $component->call('addParagraph')->assertStatus(403);

    $admin = aboutManager();
    app(AboutSettings::class)->title = '<script>alert(1)</script>';
    app(AboutSettings::class)->save();

    $this->get('/a-propos')->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});
