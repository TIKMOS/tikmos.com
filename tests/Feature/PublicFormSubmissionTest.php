<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\PublicFormSubmissionLimiter;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['cache.default' => 'array', 'cache.limiter' => 'array']);
    Cache::store('array')->flush(); // Uniquement le cache mémoire propre à ce test.
    Http::preventStrayRequests();
    Notification::fake();
    $this->travelTo(now()->setDate(2026, 10, 27)->startOfDay());
    $settings = app(GeneralSettings::class);
    $settings->emails = ['contact' => 'equipe@example.test'];
    $settings->save();
});

it('refuse le quatrième appel Livewire direct même en changeant l’email déclaré', function (bool $serviceForm): void {
    $form = $serviceForm
        ? Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        : Volt::test('pages.public.contact');
    for ($i = 1; $i <= 3; $i++) {
        $form->set('name', 'Visiteur fictif')->set('email', 'visiteur-'.$i.'@example.test')
            ->set('message', 'Besoin fictif '.$i)->call('submit')->assertHasNoErrors();
    }
    $form->set('name', 'Visiteur fictif')->set('email', 'autre@example.test')
        ->set('message', 'Ce texte doit rester disponible.')->call('submit')
        ->assertHasErrors('submission')->assertSet('message', 'Ce texte doit rester disponible.');
    expect($serviceForm ? ServiceRequest::count() : Lead::count())->toBe(3);
    Notification::assertCount($serviceForm ? 6 : 0);
    Http::assertNothingSent();
})->with(['contact' => false, 'demande de service' => true]);

function publicFormVisitor(string $name): void
{
    session()->setId(hash('sha1', $name));
}

function reservePublicForm(string $email = '', string $phone = '', string $ip = '192.0.2.10'): void
{
    app(PublicFormSubmissionLimiter::class)->ensureAllowed(
        Request::create('/contact', 'POST', server: ['REMOTE_ADDR' => $ip]), $email, $phone,
    );
}

it('conserve tous les champs et autorise la reprise exactement après dix minutes', function (bool $serviceForm): void {
    $form = $serviceForm
        ? Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        : Volt::test('pages.public.contact');
    for ($i = 1; $i <= 3; $i++) {
        $form->set('name', 'Visiteur fictif')->set('email', 'essai-'.$i.'@example.test')
            ->set('message', 'Besoin fictif')->call('submit')->assertHasNoErrors();
    }
    $form->set('name', 'Texte conservé')->set('email', 'final@example.test')
        ->set('phone', '+000 000 000')->set('message', 'Ma description ne doit pas disparaître.');
    if ($serviceForm) {
        $form->set('company', 'Organisation fictive')->set('budget_hint', 'À préciser');
    } else {
        $form->set('subject', 'Formation');
    }
    $this->travel(599)->seconds();
    $form->call('submit')->assertHasErrors('submission')->assertSee('Réessayez dans 1 seconde.')
        ->assertSet('name', 'Texte conservé')->assertSet('email', 'final@example.test')
        ->assertSet('phone', '+000 000 000')->assertSet('message', 'Ma description ne doit pas disparaître.');
    if ($serviceForm) {
        $form->assertSet('reference', null)->assertSet('company', 'Organisation fictive')->assertSet('budget_hint', 'À préciser');
        expect(DB::table('request_events')->count())->toBe(3);
    } else {
        $form->assertSet('sent', false)->assertSet('subject', 'Formation');
    }
    $this->travel(1)->seconds();
    $form->call('submit')->assertHasNoErrors()->assertSet('message', '');
    expect($serviceForm ? ServiceRequest::count() : Lead::count())->toBe(4);
    Notification::assertCount($serviceForm ? 8 : 0);
})->with(['contact' => false, 'demande de service' => true]);

it('préserve les validations sans consommer le quota des envois valides', function (bool $serviceForm): void {
    $form = $serviceForm
        ? Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        : Volt::test('pages.public.contact');
    $form->set('name', 'Fictif')->set('email', 'invalide')->set('message', 'Besoin fictif')
        ->call('submit')->assertHasErrors('email');
    $form->set('email', '')->set('phone', '')->call('submit')->assertHasErrors('phone');
    $form->set('email', 'valide@example.test')->set('message', '')->call('submit')->assertHasErrors('message');
    expect(Lead::count())->toBe(0)->and(ServiceRequest::count())->toBe(0);
    Notification::assertNothingSent();
    for ($i = 1; $i <= 3; $i++) {
        $form->set('name', 'Fictif')->set('email', 'valide@example.test')->set('message', 'Besoin valide')
            ->call('submit')->assertHasNoErrors();
    }
    expect($serviceForm ? ServiceRequest::count() : Lead::count())->toBe(3);
})->with(['contact' => false, 'demande de service' => true]);

it('préserve la soumission avec téléphone seul', function (bool $serviceForm): void {
    $form = $serviceForm
        ? Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        : Volt::test('pages.public.contact');
    $form->set('name', 'Fictif')->set('email', '')->set('phone', '+000 000 000')
        ->set('message', 'Besoin fictif')->call('submit')->assertHasNoErrors();
    expect($serviceForm ? ServiceRequest::count() : Lead::count())->toBe(1);
    Notification::assertCount($serviceForm ? 1 : 0);
})->with(['contact' => false, 'demande de service' => true]);

it('conserve le honeypot sans effet secondaire ni consommation du quota', function (): void {
    $form = Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        ->set('name', 'Fictif')->set('email', 'bot@example.test')->set('message', 'Fictif')->set('website', 'rempli');
    for ($i = 0; $i < 4; $i++) {
        $form->call('submit')->assertHasNoErrors();
    }
    expect(ServiceRequest::count())->toBe(0)->and(DB::table('request_events')->count())->toBe(0);
    Notification::assertNothingSent();
    $form->set('website', '');
    for ($i = 0; $i < 3; $i++) {
        $form->set('message', 'Besoin valide')->call('submit')->assertHasNoErrors();
    }
    expect(ServiceRequest::count())->toBe(3);
});

it('partage le quota entre le contact et toutes les fiches de service', function (): void {
    $contact = Volt::test('pages.public.contact');
    for ($i = 0; $i < 2; $i++) {
        $contact->set('name', 'Fictif')->set('email', 'contact@example.test')->set('message', 'Fictif')->call('submit')->assertHasNoErrors();
    }
    Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        ->set('name', 'Fictif')->set('email', 'service@example.test')->set('message', 'Fictif')->call('submit')->assertHasNoErrors();
    Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        ->set('name', 'Fictif')->set('email', 'autre@example.test')->set('message', 'Fictif')->call('submit')->assertHasErrors('submission');
    expect(Lead::count())->toBe(2)->and(ServiceRequest::count())->toBe(1)
        ->and(DB::table('request_events')->count())->toBe(1);
    Notification::assertCount(2);
});

it('permet à deux visiteurs distincts partageant une IP de disposer de leur quota', function (): void {
    foreach (['visiteur-a', 'visiteur-b'] as $visitor) {
        publicFormVisitor($visitor);
        for ($i = 0; $i < 3; $i++) {
            reservePublicForm($visitor.'-'.$i.'@example.test');
        }
    }
    expect(fn () => reservePublicForm('changement@example.test'))->toThrow(ValidationException::class);
});

it('ne réinitialise pas le quota visiteur quand les coordonnées et IP changent', function (): void {
    for ($i = 1; $i <= 3; $i++) {
        reservePublicForm('change-'.$i.'@example.test', '', '192.0.2.'.$i);
    }
    expect(fn () => reservePublicForm('autre@example.test', '', '198.51.100.1'))->toThrow(ValidationException::class);
});

it('utilise le compte authentifié même après un changement de session ou email déclaré', function (): void {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    for ($i = 1; $i <= 3; $i++) {
        publicFormVisitor('session-'.$i);
        reservePublicForm('declaration-'.$i.'@example.test');
    }
    publicFormVisitor('nouvelle-session');
    expect(fn () => reservePublicForm('autre@example.test'))->toThrow(ValidationException::class);
    Auth::logout();
    publicFormVisitor('visiteur-legitime');
    reservePublicForm('autre@example.test');
    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('limite la rotation de sessions et emails par un plafond réseau et laisse reprendre au délai', function (): void {
    for ($i = 1; $i <= 20; $i++) {
        publicFormVisitor('rotation-'.$i);
        reservePublicForm('rotation-'.$i.'@example.test');
    }
    publicFormVisitor('rotation-21');
    expect(fn () => reservePublicForm('nouveau@example.test'))->toThrow(ValidationException::class);
    $this->travel(599)->seconds();
    expect(fn () => reservePublicForm('nouveau@example.test'))->toThrow(ValidationException::class);
    $this->travel(1)->seconds();
    reservePublicForm('nouveau@example.test');
    expect(Lead::count())->toBe(0)->and(ServiceRequest::count())->toBe(0);
});

it('protège les coordonnées normalisées entre IP et sessions sans les traiter comme une identité', function (string $kind): void {
    for ($i = 1; $i <= 5; $i++) {
        publicFormVisitor('destinataire-'.$i);
        reservePublicForm($kind === 'email' ? ($i % 2 ? 'CIBLE@example.test' : 'cible@EXAMPLE.TEST') : '',
            $kind === 'phone' ? ($i % 2 ? '+000 000 000' : '000000000') : '', '192.0.2.'.$i);
    }
    publicFormVisitor('destinataire-6');
    $send = fn () => reservePublicForm($kind === 'email' ? 'cible@example.test' : '', $kind === 'phone' ? '000000000' : '', '198.51.100.1');
    expect($send)->toThrow(ValidationException::class);
    $this->travel(1799)->seconds();
    expect($send)->toThrow(ValidationException::class);
    $this->travel(1)->seconds();
    $send();
    expect(User::count())->toBe(0);
})->with(['email', 'phone']);

it('refuse sans effet secondaire quand le verrou est occupé puis libère la reprise', function (bool $serviceForm): void {
    $form = $serviceForm
        ? Volt::test('pages.public.services-show', ['service' => Service::factory()->create()])
        : Volt::test('pages.public.contact');
    $lock = Cache::store('array')->lock(PublicFormSubmissionLimiter::LOCK_KEY, 10);
    expect($lock->get())->toBeTrue();
    try {
        $form->set('name', 'Fictif')->set('email', 'essai@example.test')->set('message', 'Texte conservé')
            ->call('submit')->assertHasErrors('submission')->assertSee('Réessayez dans 2 secondes')
            ->assertSet('message', 'Texte conservé');
        expect(Lead::count())->toBe(0)->and(ServiceRequest::count())->toBe(0);
        Notification::assertNothingSent();
    } finally {
        $lock->release();
    }
    $form->call('submit')->assertHasNoErrors();
    expect($serviceForm ? ServiceRequest::count() : Lead::count())->toBe(1);
})->with(['contact' => false, 'demande de service' => true]);

it('refuse un cache non persistant sans transmettre les détails techniques au visiteur', function (): void {
    config(['cache.stores.f09_invalid.driver' => 'null', 'cache.limiter' => 'f09_invalid']);
    Log::spy();
    $form = Volt::test('pages.public.contact')->set('name', 'Fictif')->set('email', 'essai@example.test')
        ->set('message', 'Texte conservé')->call('submit')->assertHasErrors('submission')
        ->assertSee('Réessayez dans 30 secondes')->assertSet('message', 'Texte conservé');
    expect(Lead::count())->toBe(0);
    Notification::assertNothingSent();
    Log::shouldHaveReceived('warning')->once()->with('public_form_limiter_unavailable', ['exception_type' => RuntimeException::class]);
});

it('ne stocke aucune coordonnée brute dans ses clés et utilise le cache limiter sélectionné', function (): void {
    config(['cache.stores.f09_isolated' => ['driver' => 'array'], 'cache.limiter' => 'f09_isolated']);
    reservePublicForm('confidentiel@example.test', '+000 000 000');
    $keys = array_keys(Cache::store('f09_isolated')->getStore()->all());
    expect($keys)->not->toBeEmpty();
    foreach ($keys as $key) {
        expect($key)->not->toContain('confidentiel', '000000000', '192.0.2.10', session()->getId());
    }
    expect(Cache::store('array')->getStore()->all())->toBeEmpty();
});

it('conserve les quotas dans le cache database SQLite après recréation de son adaptateur', function (): void {
    config(['cache.stores.f09_database' => [
        'driver' => 'database', 'connection' => 'sqlite', 'table' => 'cache',
        'lock_table' => 'cache_locks', 'lock_lottery' => [0, 100],
    ], 'cache.limiter' => 'f09_database']);
    for ($i = 0; $i < 3; $i++) {
        reservePublicForm('stockage@example.test');
        Cache::purge('f09_database');
    }
    expect(fn () => reservePublicForm('autre@example.test'))->toThrow(ValidationException::class);
    expect(DB::table('cache')->count())->toBeGreaterThan(0)
        ->and(DB::table('cache_locks')->count())->toBe(0);
    $this->travel(600)->seconds();
    reservePublicForm('autre@example.test');
    expect(DB::table('cache_locks')->count())->toBe(0);
});

it('refuse une panne du cache sans exposer son message technique', function (): void {
    Log::spy();
    Cache::partialMock()->shouldReceive('store')->with('array')->once()
        ->andThrow(new RuntimeException('DETAIL_TECHNIQUE_PRIVE_FICTIF'));
    try {
        reservePublicForm('essai@example.test');
        $this->fail('Une panne du cache doit refuser la soumission.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['submission'][0])->toContain('Réessayez dans 30 secondes')
            ->not->toContain('DETAIL_TECHNIQUE_PRIVE_FICTIF');
    }
    Log::shouldHaveReceived('warning')->once()->with('public_form_limiter_unavailable', ['exception_type' => RuntimeException::class]);
    expect(Lead::count())->toBe(0)->and(ServiceRequest::count())->toBe(0);
    Notification::assertNothingSent();
});
