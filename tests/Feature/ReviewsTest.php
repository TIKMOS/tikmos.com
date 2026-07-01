<?php

use App\Enums\ParcelStatus;
use App\Enums\Role;
use App\Models\Parcel;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Models\User;
use App\Notifications\ReviewRequested;
use App\Services\ReviewSolicitationService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

function client(): User
{
    $u = User::factory()->create(['city' => 'Cotonou']);
    $u->assignRole(Role::Client->value);

    return $u;
}

function deliveredParcel(User $user, int $daysAgo = 10): Parcel
{
    return Parcel::factory()->create([
        'sender_id' => $user->id,
        'status' => ParcelStatus::Delivered->value,
        'delivered_at' => now()->subDays($daysAgo),
    ]);
}

it('sollicite un avis pour un colis livré éligible', function (): void {
    Notification::fake();
    $user = client();
    deliveredParcel($user, 10);

    $count = app(ReviewSolicitationService::class)->solicitForDeliveredParcels();

    expect($count)->toBe(1)
        ->and(ReviewRequest::where('user_id', $user->id)->exists())->toBeTrue();
    Notification::assertSentTo($user, ReviewRequested::class);
});

it('ne sollicite pas un colis livré trop récemment', function (): void {
    Notification::fake();
    deliveredParcel(client(), 1); // délai par défaut = 7 j

    expect(app(ReviewSolicitationService::class)->solicitForDeliveredParcels())->toBe(0);
});

it('ne sollicite pas deux fois (cooldown)', function (): void {
    Notification::fake();
    $user = client();
    deliveredParcel($user, 10);

    $service = app(ReviewSolicitationService::class);
    $service->solicitForDeliveredParcels();
    $second = $service->solicitForDeliveredParcels();

    expect($second)->toBe(0)
        ->and(ReviewRequest::where('user_id', $user->id)->count())->toBe(1);
});

it('permet au client de déposer un avis sur un colis livré', function (): void {
    $user = client();
    $parcel = deliveredParcel($user, 10);

    Volt::actingAs($user)->test('pages.app.colis-show', ['reference' => $parcel->reference])
        ->set('rating', 5)
        ->set('body', 'Service impeccable, je recommande vivement.')
        ->call('submitReview')
        ->assertHasNoErrors();

    $review = $parcel->reviews()->first();
    expect($review)->not->toBeNull()
        ->and($review->rating)->toBe(5)
        ->and($review->status->value)->toBe('pending');
});

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
