{{-- Section FAQ (accordéon) — alimentée par FaqItem::published(). --}}
@if ($faqItems->isNotEmpty())
<x-tk.section tone="paper">
    <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
        <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Questions fréquentes</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Tout ce qu'il faut savoir.</h2>
            <p class="mt-4 text-muted">Une question avant de nous confier votre projet ou votre commande ?</p>
            <x-tk.button href="{{ url('/faq') }}" variant="secondary" icon="arrow-right" :iconRight="true" class="mt-6">Toutes les questions</x-tk.button>
        </div>

        <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.accordion :items="$faqItems->take(6)" />
        </div>
    </div>
</x-tk.section>
@endif
