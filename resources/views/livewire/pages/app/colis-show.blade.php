<?php

use App\Enums\ParcelStatus;
use App\Enums\ReviewSource;
use App\Enums\ReviewStatus;
use App\Models\Parcel;
use App\Models\Review;
use App\Models\ReviewRequest;
use App\Settings\ReviewsSettings;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Parcel $parcel;

    #[Validate('required|integer|min:1|max:5')]
    public int $rating = 5;

    #[Validate('required|string|min:5|max:1000')]
    public string $body = '';

    public function mount(string $reference): void
    {
        $this->parcel = Parcel::with('events')->where('reference', $reference)->firstOrFail();

        abort_unless(auth()->user()->can('view', $this->parcel), 403);
    }

    #[Computed]
    public function userReview(): ?Review
    {
        return $this->parcel->reviews()->where('user_id', auth()->id())->first();
    }

    public function canReview(): bool
    {
        return $this->parcel->status === ParcelStatus::Delivered
            && $this->parcel->sender_id === auth()->id()
            && ! $this->userReview;
    }

    public function submitReview(): void
    {
        abort_unless($this->canReview(), 403);
        $this->validate();

        $user = auth()->user();
        $autoPublish = app(ReviewsSettings::class)->auto_publish;

        $this->parcel->reviews()->create([
            'user_id' => $user->id,
            'author_name' => $user->name,
            'author_city' => $user->city,
            'rating' => $this->rating,
            'body' => $this->body,
            'source' => ReviewSource::Parcel,
            'status' => $autoPublish ? ReviewStatus::Approved : ReviewStatus::Pending,
            'published_at' => $autoPublish ? now() : null,
        ]);

        ReviewRequest::where('subject_type', $this->parcel->getMorphClass())
            ->where('subject_id', $this->parcel->id)
            ->whereNull('responded_at')
            ->update(['responded_at' => now()]);

        unset($this->userReview);
        $this->reset('body');
        $this->rating = 5;
        $this->dispatch('sz-toast', type: 'success', message: 'Merci ! Votre avis a bien été envoyé.');
    }
}; ?>

<div>
    <a href="{{ route('app.colis') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content">
        <x-lucide-arrow-left class="h-4 w-4" /> Mes colis
    </a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">{{ $parcel->reference }}</h1>
            <p class="mt-1 text-muted">{{ $parcel->origin->label() }} → {{ $parcel->destination->label() }} · {{ $parcel->type->label() }}</p>
        </div>
        <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-tk.card>
                <x-tk.kicker>Suivi</x-tk.kicker>
                <div class="mt-5">
                    <x-tk.stepper :steps="$parcel->timelineSteps()" />
                </div>
            </x-tk.card>

            {{-- Avis --}}
            @if ($this->canReview())
                <x-tk.card class="border-ink/15 bg-mint/40">
                    <div class="flex items-center gap-2">
                        <x-lucide-message-circle-heart class="h-5 w-5 text-content" />
                        <h2 class="font-display text-lg font-bold text-content">Votre colis est arrivé — donnez votre avis</h2>
                    </div>
                    <p class="mt-1 text-sm text-muted">Votre retour aide la diaspora à nous faire confiance.</p>
                    <form wire:submit="submitReview" class="mt-5 space-y-4">
                        <div>
                            <p class="mb-1.5 text-[13px] font-medium text-content">Votre note</p>
                            <x-tk.rating wire:model="rating" size="lg" />
                            @error('rating')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-tk.field label="Votre avis" :error="$errors->first('body')">
                            <textarea wire:model="body" rows="3" placeholder="Comment s'est passée votre expérience ?"
                                      class="w-full rounded-input border bg-surface px-4 py-3 text-[15px] text-content placeholder:text-tk-400 focus:outline-none focus:ring-4 focus:ring-ink/10 {{ $errors->has('body') ? 'border-danger' : 'border-tk-300 focus:border-ink' }}"></textarea>
                        </x-tk.field>
                        <x-tk.button type="submit" variant="primary" icon="send">
                            <span wire:loading.remove wire:target="submitReview">Envoyer mon avis</span>
                            <span wire:loading wire:target="submitReview">…</span>
                        </x-tk.button>
                    </form>
                </x-tk.card>
            @elseif ($this->userReview)
                <x-tk.card>
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <x-tk.rating :value="$this->userReview->rating" readonly size="sm" />
                            <span class="text-sm text-muted">Votre avis</span>
                        </div>
                        <x-tk.badge :tone="$this->userReview->status->tone()">{{ $this->userReview->status === \App\Enums\ReviewStatus::Pending ? 'En modération' : $this->userReview->status->label() }}</x-tk.badge>
                    </div>
                    <p class="mt-3 text-content/80">« {{ $this->userReview->body }} »</p>
                </x-tk.card>
            @endif
        </div>

        <div class="space-y-4">
            <x-tk.card>
                <x-tk.kicker>Détails</x-tk.kicker>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Destinataire</dt><dd class="font-medium text-content">{{ $parcel->recipient_name }}</dd></div>
                    @if ($parcel->weight_kg)
                        <div class="flex justify-between gap-4"><dt class="text-muted">Poids</dt><dd class="font-medium text-content">{{ $parcel->weight_kg }} kg</dd></div>
                    @endif
                    @if ($parcel->price)
                        <div class="flex justify-between gap-4"><dt class="text-muted">Montant</dt><dd class="font-medium text-content">{{ number_format((float) $parcel->price, 0, ',', ' ') }} {{ $parcel->currency }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-muted">Paiement</dt><dd><x-tk.badge :tone="$parcel->payment_status->tone()">{{ $parcel->payment_status->label() }}</x-tk.badge></dd></div>
                </dl>
            </x-tk.card>

            <x-tk.whatsapp-button :floating="false" :message="'Bonjour SEZY, au sujet du colis '.$parcel->reference" class="w-full" />
        </div>
    </div>
</div>
