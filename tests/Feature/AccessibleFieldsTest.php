<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function accessibleFieldsDocument(string $html): DOMDocument
{
    $document = new DOMDocument;
    $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

    return $document;
}

it('relie le controle a son libelle et combine la description externe avec son erreur', function (): void {
    $document = accessibleFieldsDocument(Blade::render(<<<'BLADE'
        <p id="external-help">Information complémentaire.</p>
        <x-tk.field label="Adresse" for="address" hint="Aide" error="Adresse à vérifier" :required="true">
            <x-tk.input aria-describedby="external-help" />
        </x-tk.field>
        BLADE));
    $input = $document->getElementById('address');
    expect($input)->not->toBeNull();
    expect($input->getAttribute('aria-invalid'))->toBe('true')
        ->and($input->hasAttribute('required'))->toBeTrue()
        ->and($input->getAttribute('aria-required'))->toBe('true');
    $descriptions = preg_split('/\s+/', $input->getAttribute('aria-describedby'));
    expect($descriptions)->toContain('external-help');
    foreach ($descriptions as $id) {
        expect($document->getElementById($id))->not->toBeNull();
    }
    $error = $document->getElementById('address-error');
    expect($descriptions)->toContain($error->getAttribute('id'))
        ->and($error->getAttribute('role'))->toBe('alert')
        ->and($document->getElementById('address-hint'))->toBeNull();
    expect((new DOMXPath($document))->query('//label[@for="address"]')->length)->toBe(1);
});

it('garde des associations distinctes pour deux champs partageant le meme modele', function (): void {
    $document = accessibleFieldsDocument(Blade::render(<<<'BLADE'
        <x-tk.field label="Premier nom" for="first-name" hint="Aide du premier champ">
            <x-tk.input wire:model="name" />
        </x-tk.field>
        <x-tk.field label="Second nom" for="second-name" hint="Aide du second champ">
            <x-tk.input wire:model="name" />
        </x-tk.field>
        BLADE));
    foreach ($document->getElementsByTagName('label') as $label) {
        $control = $document->getElementById($label->getAttribute('for'));
        expect($control)->not->toBeNull();
        expect($control->hasAttribute('required'))->toBeFalse()
            ->and($control->getAttribute('aria-invalid'))->toBe('false');
        $description = $document->getElementById($control->getAttribute('aria-describedby'));
        expect($description)->not->toBeNull();
        expect($description->parentNode->isSameNode($label->parentNode))->toBeTrue();
    }
    $xpath = new DOMXPath($document);
    foreach ($xpath->query('//*[@id]') as $node) {
        expect($xpath->query('//*[@id="'.$node->getAttribute('id').'"]')->length)->toBe(1);
    }
});

it('conserve les identifiants du checkout et expose son refus sans effet metier', function (): void {
    Http::preventStrayRequests();
    Notification::fake();
    $this->seed(RolePermissionSeeder::class);
    $client = User::factory()->create(['phone' => '+22900000000']);
    $client->assignRole(Role::Client->value);
    $product = Product::create(['name' => 'Article fictif', 'slug' => 'article-a11y', 'price' => 1000, 'stock' => 2, 'is_active' => true]);
    app(CartService::class)->add($product->id);
    $page = Volt::actingAs($client)->test('pages.public.checkout');
    $before = accessibleFieldsDocument($page->html());
    $ids = [];
    foreach ((new DOMXPath($before))->query('//input[starts-with(@id,"checkout-")] | //textarea[starts-with(@id,"checkout-")]') as $control) {
        $ids[] = $control->getAttribute('id');
    }
    expect($ids)->toHaveCount(5);
    $page->set('delivery_address', str_repeat('a', 501))->call('place')->assertHasErrors('delivery_address');
    $after = accessibleFieldsDocument($page->html());
    foreach ($ids as $id) {
        $control = $after->getElementById($id);
        expect($control)->not->toBeNull();
        expect((new DOMXPath($after))->query('//label[@for="'.$id.'"]')->length)->toBe(1);
    }
    $invalid = (new DOMXPath($after))->query('//textarea[@aria-invalid="true"]')->item(0);
    expect($invalid)->not->toBeNull();
    $error = $after->getElementById($invalid->getAttribute('aria-describedby'));
    expect($error)->not->toBeNull();
    expect($error->getAttribute('role'))->toBe('alert')
        ->and(Order::count())->toBe(0)->and($product->fresh()->stock)->toBe(2);
    Notification::assertNothingSent();
    Http::assertNothingSent();
});
