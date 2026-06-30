<x-layouts.public>
    <x-slot:head>
        <x-tk.seo />
    </x-slot:head>

    @php($heroImage = $homepage->hero_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($homepage->hero_image) : asset('img/portrait-prof.jpg'))

    {{-- ============ HERO ============ --}}
    <section class="relative overflow-hidden bg-paper">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-10 px-5 py-14 sm:px-6 md:py-20 lg:grid-cols-2 lg:gap-12 lg:px-8">
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>{{ $homepage->hero_kicker }}</x-tk.kicker>
                <x-tk.divider-gold class="mt-3" />
                <h1 class="mt-4 font-display text-[2.4rem] font-black leading-[1] tracking-[-0.02em] text-content sm:text-[2.9rem] lg:text-5xl xl:text-[3.4rem]">
                    {{ $homepage->hero_title }}
                </h1>
                <p class="mt-5 max-w-md text-lg text-muted">{{ $homepage->hero_subtitle }}</p>

                {{-- CTA principaux --}}
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <x-tk.button href="{{ url('/contact') }}" variant="primary" size="lg" icon="file-text">Demander un devis</x-tk.button>
                    <x-tk.button href="{{ url('/services') }}" variant="gold" size="lg" icon="arrow-right" :iconRight="true">Découvrir mes services</x-tk.button>
                </div>

                {{-- Badges de crédibilité --}}
                <p class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                    @foreach ($homepage->trust_badges as $badge)
                        <span class="inline-flex items-center gap-1.5"><x-dynamic-component :component="'lucide-'.($badge['icon'] ?? 'check')" class="h-4 w-4 text-gold-600" /> {{ $badge['label'] }}</span>
                    @endforeach
                </p>
            </div>

            {{-- Portrait + carte flottante --}}
            <div class="relative reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="aspect-[4/5] overflow-hidden rounded-[28px] bg-tk-100 shadow-lg sm:aspect-[5/4] lg:aspect-[4/5]">
                    <img src="{{ $heroImage }}" alt="{{ $seo->person_name ?? config('app.name') }}" loading="eager" width="900" height="1125" class="h-full w-full object-cover" />
                </div>
                <div class="absolute -bottom-5 -left-3 flex items-center gap-3 rounded-2xl border border-line bg-surface/95 p-3.5 shadow-pop backdrop-blur sm:left-6"
                     x-data="{ n: 0, target: {{ $statCount }} }"
                     x-intersect.once="(() => { const s = performance.now(); const step = (t) => { const p = Math.min(1, (t - s) / 1400); n = Math.floor(p * target); if (p < 1) requestAnimationFrame(step); }; requestAnimationFrame(step); })()">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-tile bg-ink text-paper"><x-lucide-check-check class="h-5 w-5" /></span>
                    <div>
                        <p class="font-display text-2xl font-extrabold leading-none text-content"><span x-text="n.toLocaleString('fr-FR')">{{ number_format($statCount, 0, ',', ' ') }}</span></p>
                        <p class="mt-0.5 text-xs text-muted">clients et projets accompagnés</p>
                    </div>
                </div>
                <div class="absolute -top-3 right-4 rounded-2xl bg-ink px-4 py-2.5 text-paper shadow-pop">
                    <p class="font-display text-lg font-bold leading-none">Data · IA</p>
                    <p class="text-[11px] text-gold">Expertise certifiée</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ Sections configurables (ordre + activation via HomepageSettings) ============ --}}
    @foreach ($homepage->sections_order as $sectionKey)
        @continue(! ($homepage->sections_enabled[$sectionKey] ?? true))
        @includeIf('partials.home.'.$sectionKey)
    @endforeach

    {{-- ============ CTA FINAL ============ --}}
    <section class="relative overflow-hidden bg-canvas">
        <div class="relative mx-auto flex w-full max-w-3xl flex-col items-center px-5 py-20 text-center sm:px-6 md:py-24 lg:px-8 reveal"
             x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.divider-gold />
            <h2 class="mt-5 font-display text-3xl font-black text-content md:text-5xl">{{ $homepage->cta_title }}</h2>
            <p class="mt-3 max-w-md text-muted">{{ $homepage->cta_text }}</p>
            <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
                <x-tk.button href="{{ url('/contact') }}" variant="primary" size="lg" icon="file-text">Demander un devis</x-tk.button>
                <x-tk.whatsapp-button :floating="false" />
            </div>
        </div>
    </section>
</x-layouts.public>
