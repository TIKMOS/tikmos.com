@php($reviews = \App\Models\Review::approved()->latest('published_at')->latest()->get())

<x-layouts.public>
    <x-slot:head>
        <x-tk.seo title="Témoignages" description="Ce que nos clients disent de Tikmos : formations, solutions informatiques, accompagnement et boutique." />
    </x-slot:head>

    <x-tk.section tone="canvas">
        <div class="mx-auto max-w-2xl text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker class="justify-center">Témoignages</x-tk.kicker>
            <div class="mt-3 flex justify-center"><x-tk.divider-gold /></div>
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Ils nous font confiance.</h1>
            <p class="mx-auto mt-4 max-w-lg text-lg text-muted">Des clients accompagnés, des projets livrés, une exigence tenue.</p>
        </div>

        @if ($reviews->isEmpty())
            <x-tk.empty-state class="mt-10" icon="message-square-quote" title="Bientôt">Les premiers témoignages arrivent.</x-tk.empty-state>
        @else
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($reviews as $i => $review)
                    <figure class="reveal flex h-full flex-col rounded-card border border-line/60 bg-surface p-6 shadow-sm"
                            x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ ($i % 3) * 80 }})">
                        <x-tk.rating :value="(int) $review->rating" readonly size="sm" />
                        <blockquote class="mt-3 flex-1 text-content/85">« {{ $review->body }} »</blockquote>
                        <figcaption class="mt-5 flex items-center gap-3 border-t border-line pt-4">
                            <x-tk.avatar :name="$review->author_name" size="sm" />
                            <div>
                                <p class="text-sm font-semibold text-content">{{ $review->author_name }}</p>
                                @if ($review->author_city)<p class="text-xs text-muted">{{ $review->author_city }}</p>@endif
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @endif

        <div class="mt-12 flex flex-wrap justify-center gap-3">
            <x-tk.button href="{{ url('/services') }}" variant="primary" icon="arrow-right" :iconRight="true">Découvrir mes services</x-tk.button>
            <x-tk.button href="{{ route('quote-request') }}" variant="gold">Demander un devis</x-tk.button>
        </div>
    </x-tk.section>
</x-layouts.public>
