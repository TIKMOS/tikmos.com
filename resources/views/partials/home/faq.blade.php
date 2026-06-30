{{-- Section FAQ (accordéon) — alimentée par FaqItem::published(). --}}
@if ($faqItems->isNotEmpty())
<x-tk.section tone="paper">
    <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-center">
        <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <div class="relative aspect-[4/3] overflow-hidden rounded-[28px] bg-tk-100 lg:aspect-[4/5]">
                <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=800&q=70&auto=format&fit=crop"
                     alt="Colis prêts à expédier" loading="lazy" class="h-full w-full object-cover grayscale-[55%] transition-all duration-700 hover:grayscale-0" />
                <div class="absolute bottom-4 left-4 rounded-2xl bg-surface/95 px-4 py-2.5 shadow-pop backdrop-blur">
                    <p class="font-display text-sm font-bold text-content">Assurance incluse</p>
                    <p class="text-xs text-muted">Vos colis, l'esprit tranquille</p>
                </div>
            </div>
        </div>

        <div>
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>Questions fréquentes</x-tk.kicker>
                <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Tout ce qu'il faut savoir.</h2>
            </div>
            <div class="mt-8" x-data="{ open: 0 }">
                @foreach ($faqItems as $k => $faq)
                    <div class="border-b border-line">
                        <button type="button" @click="open === {{ $k }} ? open = null : open = {{ $k }}"
                                class="flex w-full items-center justify-between gap-4 py-5 text-left">
                            <span class="font-display text-lg font-bold text-content">{{ $faq->question }}</span>
                            <x-lucide-plus class="h-5 w-5 shrink-0 text-content transition-transform duration-300" x-bind:class="open === {{ $k }} && 'rotate-45'" />
                        </button>
                        <div x-show="open === {{ $k }}" x-collapse>
                            <div class="prose-sz pb-5 text-muted">{!! $faq->answer !!}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-tk.section>
@endif
