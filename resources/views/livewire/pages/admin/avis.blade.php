<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Settings\ReviewsSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'pending';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function approve(Review $review): void
    {
        $review->update(['status' => ReviewStatus::Approved, 'published_at' => $review->published_at ?? now()]);
        $this->dispatch('tk-toast', type: 'success', message: 'Avis approuvé.');
    }

    public function reject(Review $review): void
    {
        $review->update(['status' => ReviewStatus::Rejected, 'is_featured' => false]);
        $this->dispatch('tk-toast', type: 'success', message: 'Avis rejeté.');
    }

    public function toggleFeatured(Review $review): void
    {
        $min = app(ReviewsSettings::class)->min_rating_to_feature;

        if (! $review->is_featured) {
            if ($review->status !== ReviewStatus::Approved || $review->rating < $min) {
                $this->dispatch('tk-toast', type: 'error', message: "Avis approuvé et noté ≥ {$min} requis pour la mise en avant.");

                return;
            }
        }

        $review->update(['is_featured' => ! $review->is_featured]);
    }

    public function delete(Review $review): void
    {
        $review->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Avis supprimé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = Review::with('user')->latest();

        if (in_array($this->filter, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $this->filter);
        } elseif ($this->filter === 'featured') {
            $query->where('is_featured', true);
        }

        return [
            'reviews' => $query->paginate(12),
            'counts' => [
                'pending' => Review::where('status', 'pending')->count(),
                'approved' => Review::where('status', 'approved')->count(),
                'featured' => Review::where('is_featured', true)->count(),
            ],
        ];
    }
}; ?>

<div>
    <div>
        <x-tk.kicker>Back-office</x-tk.kicker>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Avis</h1>
        <p class="mt-1 text-muted">Modérez les avis et choisissez ceux mis en avant sur l'accueil.</p>
    </div>

    {{-- Filtres --}}
    <div class="mt-6 flex flex-wrap gap-2">
        @php($filters = ['pending' => 'En attente ('.$counts['pending'].')', 'approved' => 'Approuvés ('.$counts['approved'].')', 'featured' => 'En avant ('.$counts['featured'].')', 'rejected' => 'Rejetés', 'all' => 'Tous'])
        @foreach ($filters as $key => $label)
            <button wire:click="setFilter('{{ $key }}')"
                    @class([
                        'rounded-pill border px-4 py-1.5 text-sm font-medium transition-colors',
                        'border-ink bg-ink text-paper' => $filter === $key,
                        'border-line bg-surface text-content hover:border-ink' => $filter !== $key,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="mt-6 space-y-3">
        @forelse ($reviews as $review)
            <div wire:key="review-{{ $review->id }}" class="rounded-card border border-line bg-surface p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <x-tk.avatar :name="$review->author_name" size="sm" />
                        <div>
                            <p class="font-medium text-content">{{ $review->author_name }}@if ($review->author_city)<span class="text-muted"> · {{ $review->author_city }}</span>@endif</p>
                            <div class="mt-0.5 flex items-center gap-2">
                                <x-tk.rating :value="$review->rating" readonly size="sm" />
                                <x-tk.badge :tone="$review->source->value === 'parcel' ? 'info' : 'neutral'">{{ $review->source->label() }}</x-tk.badge>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <x-tk.badge :tone="$review->status->tone()" dot>{{ $review->status->label() }}</x-tk.badge>
                        @if ($review->is_featured)<x-tk.badge tone="info">En avant</x-tk.badge>@endif
                    </div>
                </div>
                <p class="mt-3 text-content/80">« {{ $review->body }} »</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($review->status !== \App\Enums\ReviewStatus::Approved)
                        <x-tk.button wire:click="approve({{ $review->id }})" variant="primary" size="sm" icon="check">Approuver</x-tk.button>
                    @endif
                    @if ($review->status !== \App\Enums\ReviewStatus::Rejected)
                        <x-tk.button wire:click="reject({{ $review->id }})" variant="secondary" size="sm" icon="x">Rejeter</x-tk.button>
                    @endif
                    @can('reviews.feature')
                        <x-tk.button wire:click="toggleFeatured({{ $review->id }})" variant="{{ $review->is_featured ? 'accent' : 'ghost' }}" size="sm" icon="star">{{ $review->is_featured ? 'Retirer de l\'accueil' : 'Mettre en avant' }}</x-tk.button>
                    @endcan
                    <button wire:click="delete({{ $review->id }})" wire:confirm="Supprimer cet avis ?" class="ml-auto grid h-9 w-9 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                </div>
            </div>
        @empty
            <x-tk.empty-state icon="star" title="Aucun avis">Aucun avis dans cette catégorie.</x-tk.empty-state>
        @endforelse
    </div>

    @if ($reviews->hasPages())<div class="mt-6">{{ $reviews->links() }}</div>@endif
</div>
