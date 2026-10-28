@props([
    'title',
    'updated' => null,
    'toc' => [],     // [ ['id' => '', 'title' => ''], ... ]
    'body' => '',    // HTML déjà ancré (ids sur les <h2>)
])

<x-tk.section>
    <div class="mx-auto max-w-5xl">
        <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Légal</x-tk.kicker>
            <h1 class="mt-3 font-display text-4xl font-black tracking-[-0.02em] text-content md:text-5xl">{{ $title }}</h1>
            @if ($updated)
                <p class="mt-2 inline-flex items-center gap-1.5 text-sm text-muted"><x-lucide-calendar-clock class="h-4 w-4" /> Dernière mise à jour : {{ $updated }}</p>
            @endif
        </div>

        @isset($notice)
            <div class="mt-6">{{ $notice }}</div>
        @endisset

        <div
            x-data="{
                active: @js($toc[0]['id'] ?? ''),
                init() {
                    const obs = new IntersectionObserver((entries) => {
                        entries.forEach((e) => { if (e.isIntersecting) this.active = e.target.id });
                    }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });
                    this.$root.querySelectorAll('h2[id]').forEach((s) => obs.observe(s));
                },
            }"
            class="mt-10 grid gap-10 lg:grid-cols-[230px_1fr]"
        >
            {{-- Sommaire sticky --}}
            @if (! empty($toc))
                <aside class="hidden lg:block">
                    <nav class="sticky top-24 space-y-1 border-l border-line pl-4 text-sm" aria-label="Sommaire">
                        <p class="mb-2 -ml-4 pl-4 text-xs font-semibold uppercase tracking-[0.18em] text-muted">Sommaire</p>
                        @foreach ($toc as $item)
                            <a href="#{{ $item['id'] }}"
                               class="block rounded-r-btn py-1.5 pl-3 -ml-px border-l-2 transition-colors"
                               :class="active === @js($item['id']) ? 'border-ink font-semibold text-content' : 'border-transparent text-muted hover:text-content'">{{ $item['title'] }}</a>
                        @endforeach
                    </nav>
                </aside>
            @endif

            {{-- Contenu riche --}}
            <div>
                <div class="prose-sz max-w-none leading-relaxed text-content/80">{!! $body !!}</div>
                @if ($slot->isNotEmpty())
                    <p class="mt-10 border-t border-line pt-6 text-sm text-muted">{{ $slot }}</p>
                @endif
            </div>
        </div>
    </div>
</x-tk.section>
