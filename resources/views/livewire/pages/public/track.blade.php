<?php

use App\Models\Parcel;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Validate('required|string|max:30')]
    public string $reference = '';

    public bool $searched = false;

    public function mount(?string $reference = null): void
    {
        if ($reference !== null && $reference !== '') {
            $this->reference = $reference;
            $this->searched = true;
        }
    }

    public function search(): void
    {
        $this->validate();
        $this->searched = true;
    }

    #[Computed]
    public function parcel(): ?Parcel
    {
        if (! $this->searched) {
            return null;
        }

        return Parcel::with('events')
            ->where('reference', mb_strtoupper(trim($this->reference)))
            ->first();
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Suivi de colis" description="Suivez votre colis SEZY en temps réel avec votre référence." />
    @endpush

    <x-tk.section>
        <div class="mx-auto max-w-2xl">
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>Suivi en temps réel</x-tk.kicker>
                <h1 class="mt-4 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Où est mon colis ?</h1>
                <p class="mt-4 text-lg text-muted">Entrez votre référence (ex. <span class="font-mono text-content">SZ-2026-00001</span>) pour suivre chaque étape, en direct.</p>
            </div>

            <div class="mt-7 rounded-[24px] border border-line bg-surface p-4 shadow-lg sm:p-5">
                <form wire:submit="search" class="flex flex-col gap-3 sm:flex-row">
                    <div class="flex-1">
                        <x-tk.input wire:model="reference" icon="search" placeholder="SZ-2026-…" :invalid="$errors->has('reference')" aria-label="Référence du colis" />
                    </div>
                    <x-tk.button type="submit" variant="primary" size="lg" icon="search">
                        <span wire:loading.remove wire:target="search">Suivre</span>
                        <span wire:loading wire:target="search">Recherche…</span>
                    </x-tk.button>
                </form>
                @error('reference')<p class="mt-2 px-1 text-sm text-danger">{{ $message }}</p>@enderror
            </div>

            {{-- Légende des statuts --}}
            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted">
                <span class="font-medium uppercase tracking-wider text-tk-400">Parcours :</span>
                @foreach (\App\Enums\ParcelStatus::timeline() as $st)
                    <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-ink"></span>{{ $st->label() }}</span>
                    @unless ($loop->last)<x-lucide-chevron-right class="h-3 w-3 text-tk-300" />@endunless
                @endforeach
            </div>

            <div class="mt-8" wire:loading.flex wire:target="search" style="display:none">
                <x-tk.skeleton variant="timeline" :lines="4" class="w-full" />
            </div>

            <div wire:loading.remove wire:target="search">
                @if ($this->searched && $this->parcel)
                    @php($parcel = $this->parcel)
                    <x-tk.card class="mt-8">
                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
                            <div>
                                <p class="font-mono text-sm font-bold tracking-wide">{{ $parcel->reference }}</p>
                                <p class="mt-1 text-sm text-muted">{{ $parcel->origin->label() }} → {{ $parcel->destination->label() }}</p>
                            </div>
                            <x-tk.badge :tone="$parcel->status->tone()" dot>{{ $parcel->status->label() }}</x-tk.badge>
                        </div>
                        <div class="pt-6">
                            <x-tk.stepper :steps="$parcel->timelineSteps()" />
                        </div>
                    </x-tk.card>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <p class="text-sm text-muted">Une question sur ce colis ?</p>
                        <x-tk.whatsapp-button :floating="false" :message="'Bonjour SEZY, au sujet du colis '.$parcel->reference" />
                    </div>
                @elseif ($this->searched)
                    <div class="mt-8">
                        <x-tk.empty-state icon="package-x" title="Référence introuvable">
                            Vérifiez votre référence ou contactez-nous : nous retrouverons votre colis.
                            <x-slot:action>
                                <x-tk.whatsapp-button :floating="false" />
                            </x-slot:action>
                        </x-tk.empty-state>
                    </div>
                @endif
            </div>
        </div>
    </x-tk.section>
</div>
