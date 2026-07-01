{{-- Section témoignages — avis « featured » (V2-04), avec fallback. --}}
@php
    $featured = $featuredReviews ?? collect();
    $reviews = $featured->isNotEmpty()
        ? $featured->map(fn ($r) => ['q' => $r->body, 'a' => $r->author_name, 'c' => $r->author_city ?: '', 'r' => (int) $r->rating])->take(6)->values()->all()
        : [
            ['q' => 'Formation data/IA très concrète pour mon équipe. Résultats immédiats.', 'a' => 'Awa D.', 'c' => 'Cotonou', 'r' => 5],
            ['q' => 'Solution informatique livrée dans les délais et parfaitement adaptée.', 'a' => 'Marc T.', 'c' => 'Abidjan', 'r' => 5],
            ['q' => 'Accompagnement sérieux et humain. On sent la rigueur.', 'a' => 'Fatou B.', 'c' => 'Paris', 'r' => 5],
            ['q' => 'Installation impeccable et conseils précieux. Équipe réactive.', 'a' => 'Joël A.', 'c' => 'Lomé', 'r' => 4],
            ['q' => 'Des chaussures magnifiques, commande simple et suivie.', 'a' => 'Sandra M.', 'c' => 'Cotonou', 'r' => 5],
            ['q' => "Orientation professionnelle qui m'a vraiment aidé à y voir clair.", 'a' => 'Kévin O.', 'c' => 'Dakar', 'r' => 5],
        ];
@endphp

<x-tk.section tone="canvas">
    {{-- En-tête centré --}}
    <div class="mx-auto max-w-2xl text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
        <x-tk.kicker class="justify-center">Témoignages</x-tk.kicker>
        <div class="mt-3 flex justify-center"><x-tk.divider-gold /></div>
        <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Ils nous font confiance.</h2>
        <div class="mt-4 flex items-center justify-center gap-2">
            <x-tk.rating :value="5" readonly size="sm" />
            <span class="text-sm text-muted">La satisfaction, notre meilleure preuve.</span>
        </div>
    </div>

    {{-- Grille de témoignages --}}
    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($reviews as $i => $r)
            <figure class="reveal flex h-full flex-col rounded-card border border-line/60 bg-surface p-6 shadow-sm"
                    x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ ($i % 3) * 80 }})">
                <x-tk.rating :value="$r['r']" readonly size="sm" />
                <blockquote class="mt-3 flex-1 text-content/85">« {{ $r['q'] }} »</blockquote>
                <figcaption class="mt-5 flex items-center gap-3 border-t border-line pt-4">
                    <x-tk.avatar :name="$r['a']" size="sm" />
                    <div>
                        <p class="text-sm font-semibold text-content">{{ $r['a'] }}</p>
                        @if ($r['c'])<p class="text-xs text-muted">{{ $r['c'] }}</p>@endif
                    </div>
                </figcaption>
            </figure>
        @endforeach
    </div>

    <div class="mt-10 text-center">
        <x-tk.button href="{{ url('/temoignages') }}" variant="secondary" icon="arrow-right" :iconRight="true">Tous les témoignages</x-tk.button>
    </div>
</x-tk.section>
