<?php

use App\Enums\ServiceCategory;
use App\Models\Service;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Url(as: 'cat')]
    public string $category = '';

    /** @return Collection<int, Service> */
    public function with(): array
    {
        $query = Service::query()->published();

        if ($this->category !== '' && ServiceCategory::tryFrom($this->category)) {
            $query->where('category', $this->category);
        }

        return [
            'services' => $query->get(),
            'categories' => ServiceCategory::cases(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Services" description="Formation, conception de solutions informatiques, installation, assistance et orientation : l'expertise qui exécute, au service de vos projets." />
    @endpush

    <x-tk.section tone="canvas">
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Mes services</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Une expertise rare, au service de vos projets.</h1>
            <p class="mt-4 text-lg text-muted">De la formation à la conception de solutions sur mesure — choisissez un service et demandez votre devis.</p>
        </div>

        {{-- Filtres catégories --}}
        <div class="mt-8 flex flex-wrap gap-2">
            <button wire:click="$set('category', '')"
                    class="rounded-btn border px-4 py-2 text-sm font-medium transition-colors {{ $category === '' ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">Tous</button>
            @foreach ($categories as $cat)
                <button wire:click="$set('category', '{{ $cat->value }}')"
                        class="rounded-btn border px-4 py-2 text-sm font-medium transition-colors {{ $category === $cat->value ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">{{ $cat->label() }}</button>
            @endforeach
        </div>

        @if ($services->isEmpty())
            <x-tk.empty-state class="mt-10" icon="search-x" title="Aucun service dans cette catégorie">Revenez bientôt ou contactez-nous directement.</x-tk.empty-state>
        @else
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($services as $service)
                    <x-tk.service-card :service="$service" wire:key="svc-{{ $service->id }}" />
                @endforeach
            </div>
        @endif

        <div class="mt-12 flex flex-wrap items-center gap-3 rounded-card border border-line bg-surface p-6">
            <div class="mr-auto">
                <p class="font-display text-lg font-bold text-content">Un besoin sur mesure ?</p>
                <p class="text-sm text-muted">Décrivez votre projet, je vous réponds avec un devis clair.</p>
            </div>
            <x-tk.button href="{{ route('quote-request') }}" variant="primary" icon="file-text">Demander un devis</x-tk.button>
            <x-tk.whatsapp-button :floating="false" />
        </div>
    </x-tk.section>
</div>
