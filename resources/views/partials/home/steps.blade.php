{{-- Section « comment ça marche » — alimentée par HomepageSettings::steps --}}
<x-tk.section tone="canvas">
    <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
        <x-tk.kicker>Mon approche</x-tk.kicker>
        <x-tk.divider-gold class="mt-3" />
        <h2 class="mt-3 max-w-2xl font-display text-3xl font-extrabold text-content md:text-4xl">De l'idée à la livraison, en clair.</h2>
    </div>
    @php($tints = ['bg-indigo-50', 'bg-gold-100', 'bg-sand', 'bg-mist'])
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ($homepage->steps as $i => $step)
            <div class="reveal rounded-card border border-line/60 {{ $tints[$i % 4] }} p-7" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 100 }})">
                <span class="font-display text-5xl font-black text-gold-ink">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <h3 class="mt-3 font-display text-xl font-bold text-content">{{ $step['title'] }}</h3>
                <p class="mt-2 text-muted">{{ $step['desc'] }}</p>
            </div>
        @endforeach
    </div>
</x-tk.section>
