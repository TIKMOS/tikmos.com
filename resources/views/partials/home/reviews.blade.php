{{-- Section témoignages — avis « featured » (V2-04), avec fallback. --}}
@php
    $featured = $featuredReviews ?? collect();
    $reviews = $featured->isNotEmpty()
        ? $featured->map(fn ($r) => ['q' => $r->body, 'a' => $r->author_name, 'c' => $r->author_city ?: '', 'r' => (int) $r->rating])->take(6)->values()->all()
        : [
            ['q' => 'Colis envoyé un mardi, récupéré à Paris le jeudi. Impeccable.', 'a' => 'Aïcha K.', 'c' => 'Paris', 'r' => 5],
            ['q' => 'Enfin un service sérieux pour envoyer à ma famille à Cotonou.', 'a' => 'Marc D.', 'c' => 'Bruxelles', 'r' => 5],
            ['q' => 'Ils ont acheté ma commande Zara et tout est arrivé nickel.', 'a' => 'Fatou B.', 'c' => 'Cotonou', 'r' => 5],
            ['q' => 'Suivi clair, équipe réactive sur WhatsApp. Je recommande à 100%.', 'a' => 'Joël A.', 'c' => 'Paris', 'r' => 4],
            ['q' => 'Première fois que j\'envoie un colis aussi sereinement. Merci !', 'a' => 'Sandra M.', 'c' => 'Lyon', 'r' => 5],
            ['q' => 'Rapide, fiable et le paiement en Mobile Money est super pratique.', 'a' => 'Kévin O.', 'c' => 'Cotonou', 'r' => 5],
        ];
@endphp

<x-tk.section tone="canvas">
    {{-- En-tête centré --}}
    <div class="mx-auto max-w-2xl text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
        <x-tk.kicker class="justify-center">Ils nous font confiance</x-tk.kicker>
        <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">La diaspora nous confie l'essentiel.</h2>
        <div class="mt-4 flex items-center justify-center gap-2">
            <x-tk.rating :value="5" readonly size="sm" />
            <span class="text-sm text-muted">4,9 / 5 · +250 avis</span>
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
</x-tk.section>
