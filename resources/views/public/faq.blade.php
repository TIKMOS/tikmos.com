@php($groups = \App\Models\FaqItem::published()->orderBy('position')->get()->groupBy(fn ($f) => $f->category ?: 'Général'))

<x-layouts.public>
    <x-slot:head>
        <x-tk.seo title="FAQ" description="Questions fréquentes sur les services Tikmos : formation, solutions informatiques, boutique et accompagnement." />
        @php($allFaqs = $groups->flatten())
        @if ($allFaqs->isNotEmpty())
            @push('meta')
                <script type="application/ld+json">{!! json_encode([
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => $allFaqs->map(fn ($f) => [
                        '@type' => 'Question',
                        'name' => $f->question,
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags((string) $f->answer)],
                    ])->values()->all(),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
            @endpush
        @endif
    </x-slot:head>

    <x-tk.section>
        <div class="mx-auto max-w-2xl text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker class="justify-center">FAQ</x-tk.kicker>
            <div class="mt-3 flex justify-center"><x-tk.divider-gold /></div>
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Questions fréquentes</h1>
            <p class="mx-auto mt-4 max-w-lg text-lg text-muted">Tout ce qu'il faut savoir avant de nous confier votre projet ou votre commande.</p>
        </div>

        @if ($groups->isEmpty())
            <x-tk.empty-state class="mt-10" icon="help-circle" title="Bientôt">Les questions fréquentes arrivent.</x-tk.empty-state>
        @else
            <div class="mx-auto mt-10 max-w-3xl space-y-10">
                @foreach ($groups as $category => $items)
                    <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                        <h2 class="font-mono text-xs uppercase tracking-[0.18em] text-gold-600">{{ $category }}</h2>
                        <x-tk.accordion :items="$items" class="mt-3" />
                    </div>
                @endforeach
            </div>

            <div class="mx-auto mt-12 max-w-3xl rounded-card border border-line bg-canvas p-6 text-center">
                <p class="font-display text-lg font-bold text-content">Vous ne trouvez pas votre réponse ?</p>
                <div class="mt-4 flex flex-wrap justify-center gap-3">
                    <x-tk.button href="{{ url('/contact') }}" variant="primary" icon="message-circle">Nous contacter</x-tk.button>
                    <x-tk.whatsapp-button :floating="false" />
                </div>
            </div>
        @endif
    </x-tk.section>
</x-layouts.public>
