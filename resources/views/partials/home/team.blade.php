{{-- Section équipe (aperçu) — « une équipe derrière chaque projet » --}}
<x-tk.section>
    <div class="flex flex-wrap items-end justify-between gap-4 reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
        <div>
            <x-tk.kicker>L'équipe</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h2 class="mt-3 max-w-2xl font-display text-3xl font-extrabold text-content md:text-4xl">Une équipe derrière chaque projet.</h2>
        </div>
        <x-tk.button href="{{ url('/equipe') }}" variant="secondary" icon="arrow-right" :iconRight="true">Rencontrer l'équipe</x-tk.button>
    </div>

    @if ($teamMembers->isNotEmpty())
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($teamMembers as $i => $member)
                <div class="reveal group rounded-card border border-line/60 bg-surface p-5 transition-all hover:-translate-y-1 hover:shadow-pop"
                     x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 80 }})">
                    <div class="aspect-square overflow-hidden rounded-tile bg-tk-100">
                        @if ($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" loading="lazy" class="h-full w-full object-cover grayscale transition duration-500 group-hover:grayscale-0">
                        @else
                            <div class="grid h-full place-items-center"><x-tk.avatar :name="$member->name" size="lg" /></div>
                        @endif
                    </div>
                    <h3 class="mt-4 font-display text-lg font-bold text-content">{{ $member->name }}</h3>
                    <p class="mt-0.5 text-sm text-gold-600">{{ $member->role_title }}</p>
                </div>
            @endforeach
        </div>
    @else
        <p class="mt-8 text-muted">L'équipe sera présentée très bientôt.</p>
    @endif
</x-tk.section>
