{{-- Section réalisations (aperçu) — alimentée par Project::published() --}}
@if (($featuredProjects ?? collect())->isNotEmpty())
<x-tk.section tone="canvas">
    <div class="flex flex-wrap items-end justify-between gap-4 reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
        <div>
            <x-tk.kicker>Réalisations</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h2 class="mt-3 max-w-2xl font-display text-3xl font-extrabold text-content md:text-4xl">Des projets menés de bout en bout.</h2>
            <p class="mt-3 max-w-md text-muted">Pour vos projets ambitieux, des résultats concrets.</p>
        </div>
        <x-tk.button href="{{ url('/realisations') }}" variant="secondary" icon="arrow-right" :iconRight="true">Toutes les réalisations</x-tk.button>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($featuredProjects as $i => $project)
            <div class="reveal" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 80 }})">
                <x-tk.project-card :project="$project" />
            </div>
        @endforeach
    </div>
</x-tk.section>
@endif
