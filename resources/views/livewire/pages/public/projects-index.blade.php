<?php

use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Url(as: 'cat')]
    public string $category = '';

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = Project::query()->published();

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        return [
            'projects' => $query->get(),
            'categories' => Project::query()->published()
                ->whereNotNull('category')
                ->distinct()->orderBy('category')->pluck('category'),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Réalisations" description="Études de cas : plateformes data, applications métier, projets IA et formations menés de bout en bout." />
    @endpush

    <x-tk.section tone="canvas">
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Réalisations</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Des projets menés de bout en bout.</h1>
            <p class="mt-4 text-lg text-muted">Pour vos projets ambitieux, des résultats concrets.</p>
        </div>

        @if ($categories->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                <button wire:click="$set('category', '')" class="rounded-btn border px-4 py-2 text-sm font-medium transition-colors {{ $category === '' ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">Tous</button>
                @foreach ($categories as $cat)
                    <button wire:click="$set('category', @js($cat))" class="rounded-btn border px-4 py-2 text-sm font-medium transition-colors {{ $category === $cat ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">{{ $cat }}</button>
                @endforeach
            </div>
        @endif

        @if ($projects->isEmpty())
            <x-tk.empty-state class="mt-10" icon="folder-open" title="Bientôt">Les réalisations seront présentées ici très vite.</x-tk.empty-state>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($projects as $project)
                    <x-tk.project-card :project="$project" wire:key="prj-{{ $project->id }}" />
                @endforeach
            </div>
        @endif

        <div class="mt-12 flex flex-wrap items-center gap-3 rounded-card border border-line bg-surface p-6">
            <p class="mr-auto font-display text-lg font-bold text-content">Un projet similaire en tête ?</p>
            <x-tk.button href="{{ url('/contact') }}" variant="primary" icon="file-text">Demander un devis</x-tk.button>
        </div>
    </x-tk.section>
</div>
