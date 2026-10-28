{{-- Section services — alimentée par HomepageSettings::services --}}
<x-tk.section>
    <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
        <x-tk.kicker>Mes services</x-tk.kicker>
        <x-tk.divider-gold class="mt-3" />
        <h2 class="mt-3 max-w-2xl font-display text-3xl font-extrabold text-content md:text-4xl">Une expertise rare, mise au service de vos projets.</h2>
    </div>
    @php($tints = ['bg-indigo-50', 'bg-gold-100', 'bg-sand', 'bg-mist'])
    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($homepage->services as $i => $service)
            <div class="reveal" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 80 }})">
                <a href="{{ url($service['href'] ?? '/services') }}" wire:navigate
                   class="group flex h-full flex-col rounded-card border border-line/60 {{ $service['tint'] ?? $tints[$i % 4] }} p-6 transition-all hover:-translate-y-1 hover:shadow-pop">
                    <span class="grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper">
                        <x-dynamic-component :component="'lucide-'.($service['icon'] ?? 'package')" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-bold text-content">{{ $service['title'] }}</h3>
                    <p class="mt-2 flex-1 text-sm text-muted">{{ $service['desc'] }}</p>
                    @if ($service['soon'] ?? false)
                        <span class="mt-4"><x-tk.badge tone="neutral">Bientôt</x-tk.badge></span>
                    @else
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-content group-hover:gap-2">En savoir plus <x-lucide-arrow-right class="h-4 w-4 transition-all" /></span>
                    @endif
                </a>
            </div>
        @endforeach
    </div>
</x-tk.section>
