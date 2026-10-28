<?php

use App\Enums\QuoteStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\QuoteService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ServiceRequest $request;

    public function mount(ServiceRequest $request): void
    {
        // Scoping : le client n'accède qu'à ses propres demandes.
        abort_unless($request->user_id === Auth::id(), 403);
        $this->request = $request->load(['service', 'quote', 'events.author']);
    }

    public function acceptQuote(QuoteService $quotes): void
    {
        $this->resetValidation('quote');
        $client = Auth::user();
        abort_unless($client instanceof User, 403);
        $this->request->refresh()->load('quote');
        $quotes->decide($this->request, $client, QuoteStatus::Accepted);
        $this->request->refresh()->load(['quote', 'events.author']);
        $this->dispatch('tk-toast', type: 'success', message: 'Devis accepté. Nous revenons vers vous rapidement.');
    }

    public function refuseQuote(QuoteService $quotes): void
    {
        $this->resetValidation('quote');
        $client = Auth::user();
        abort_unless($client instanceof User, 403);
        $this->request->refresh()->load('quote');
        $quotes->decide($this->request, $client, QuoteStatus::Refused);
        $this->request->refresh()->load(['quote', 'events.author']);
        $this->dispatch('tk-toast', type: 'success', message: 'Devis refusé.');
    }
}; ?>

<div>
    <a href="{{ route('app.requests') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Mes demandes</a>

    <div class="mt-3">
        <h1 class="font-display text-3xl font-extrabold text-content">{{ $request->service?->title ?? 'Demande sur mesure' }}</h1>
        <p class="mt-1 font-mono text-sm text-muted">{{ $request->reference }} · <x-tk.badge :tone="$request->status->tone()">{{ $request->status->label() }}</x-tk.badge></p>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            {{-- Devis --}}
            @if ($request->quote)
                @php($quoteStatus = $request->quote->displayStatus())
                <div class="rounded-card border border-line bg-surface p-6">
                    <div class="flex items-center justify-between">
                        <x-tk.kicker>Votre devis</x-tk.kicker>
                        <x-tk.badge :tone="$quoteStatus->tone()">{{ $quoteStatus->label() }}</x-tk.badge>
                    </div>
                    <p class="mt-3 font-display text-3xl font-black text-content">{{ $request->quote->amountLabel() }}</p>
                    <p class="mt-1 break-words font-mono text-sm text-muted">{{ $request->quote->reference }}</p>
                    @if ($request->quote->valid_until)<p class="mt-1 text-sm text-muted">Valable jusqu'au {{ $request->quote->valid_until->format('d/m/Y') }} inclus ({{ config('app.timezone') }}).</p>@endif
                    @if ($request->quote->body)<div class="mt-4 rounded-tile bg-canvas p-4 text-sm text-content/80">{{ $request->quote->body }}</div>@endif

                    @error('quote')<p role="alert" class="mt-4 rounded-tile bg-danger-soft p-4 text-sm text-danger-ink">{{ $message }}</p>@enderror

                    @if ($quoteStatus === \App\Enums\QuoteStatus::Expired)
                        <div class="mt-5 space-y-3 rounded-tile bg-canvas p-4">
                            <p class="text-sm text-content">Ce devis a expiré. Contactez notre équipe en précisant la référence {{ $request->quote->reference }} pour demander son renouvellement.</p>
                            <x-tk.button :href="route('contact')" variant="secondary" class="w-full sm:w-auto"><span class="whitespace-normal">Demander le renouvellement</span></x-tk.button>
                        </div>
                    @elseif ($quoteStatus === \App\Enums\QuoteStatus::Sent)
                        <div class="mt-5 flex flex-wrap gap-3">
                            <x-tk.button wire:click="acceptQuote" wire:confirm="Accepter ce devis ?" variant="primary" icon="check">Accepter le devis</x-tk.button>
                            <x-tk.button wire:click="refuseQuote" wire:confirm="Refuser ce devis ?" variant="secondary" icon="x">Refuser</x-tk.button>
                        </div>
                    @endif
                </div>
            @else
                <div class="rounded-card border border-dashed border-tk-300 bg-surface p-6 text-sm text-muted">
                    Votre demande est en cours d'étude. Vous recevrez votre devis ici.
                </div>
            @endif

            {{-- Ma demande --}}
            <div class="rounded-card border border-line bg-surface p-6">
                <x-tk.kicker>Ma demande</x-tk.kicker>
                <p class="mt-3 whitespace-pre-line text-sm text-content/80">{{ $request->message }}</p>
            </div>
        </div>

        {{-- Timeline --}}
        <div>
            <div class="rounded-card border border-line bg-surface p-6">
                <x-tk.kicker>Suivi</x-tk.kicker>
                <ol class="mt-4 space-y-3">
                    @forelse ($request->events as $event)
                        <li wire:key="ev-{{ $event->id }}" class="flex gap-3">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-gold"></span>
                            <div>
                                <p class="text-sm font-medium text-content">{{ $event->from_status === $event->to_status ? 'Informations de la demande mises à jour' : $event->to_status->label() }}</p>
                                <p class="text-xs text-muted">{{ $event->created_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-muted">Aucun événement.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
</div>
