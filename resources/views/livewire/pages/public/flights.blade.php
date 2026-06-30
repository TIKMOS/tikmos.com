<?php

use App\Models\Flight;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    /** @return array<string, mixed> */
    public function with(): array
    {
        $next = Flight::nextDeparture();

        return [
            'next' => $next,
            'flights' => Flight::query()->public()->upcoming()
                ->when($next, fn ($q) => $q->whereKeyNot($next->id))
                ->get(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Prochains vols" description="Calendrier des prochains vols SEZY pour vos envois de colis Paris ↔ Cotonou." />
    @endpush

    <x-tk.section>
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Prochains vols</x-tk.kicker>
            <h1 class="mt-4 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Planifiez votre envoi.</h1>
            <p class="mt-4 max-w-md text-lg text-muted">Voici nos prochains départs sur le corridor. Réservez une place pour votre colis en un message.</p>
        </div>

        {{-- Prochain départ (mis en avant, compte à rebours) --}}
        @if ($next)
            <div class="mt-10 overflow-hidden rounded-card border border-ink/15 bg-ink text-paper reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="grid gap-6 p-7 sm:grid-cols-[1fr_auto] sm:items-center sm:p-9">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-pill bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-paper"><x-lucide-plane-takeoff class="h-3.5 w-3.5" /> Prochain départ</span>
                        <p class="mt-4 font-display text-3xl font-black md:text-4xl">{{ $next->direction->label() }}</p>
                        <p class="mt-1.5 flex items-center gap-1.5 text-tk-300"><x-lucide-calendar class="h-4 w-4" /> {{ $next->departs_on->translatedFormat('l j F Y') }}</p>
                        @if (! $next->isClosed())
                            <div class="mt-5 flex gap-2.5"
                                 x-data="{ d:0, h:0, m:0, s:0, target: {{ $next->deadline()->timestamp }} * 1000,
                                    tick() { let diff = Math.max(0, this.target - Date.now()); this.d = Math.floor(diff/86400000); this.h = Math.floor(diff%86400000/3600000); this.m = Math.floor(diff%3600000/60000); this.s = Math.floor(diff%60000/1000); } }"
                                 x-init="tick(); setInterval(() => tick(), 1000)">
                                @foreach (['d' => 'jours', 'h' => 'h', 'm' => 'min', 's' => 'sec'] as $u => $lbl)
                                    <div class="min-w-[58px] rounded-tile bg-white/10 px-3 py-2 text-center">
                                        <p class="font-display text-2xl font-bold tabular-nums" x-text="String({{ $u }}).padStart(2,'0')">00</p>
                                        <p class="text-[10px] uppercase tracking-wide text-tk-400">{{ $lbl }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="mt-5 inline-block"><x-tk.badge tone="neutral">Dépôts clôturés</x-tk.badge></span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-3 sm:items-end">
                        <x-tk.badge :tone="$next->status->tone()" dot>{{ $next->status->label() }}</x-tk.badge>
                        @unless ($next->isClosed())
                            <x-tk.whatsapp-button :floating="false" :message="'Bonjour SEZY, je souhaite réserver une place pour le vol '.$next->direction->label().' du '.$next->departs_on->format('d/m/Y')" />
                        @endunless
                    </div>
                </div>
            </div>
        @endif

        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @forelse ($flights as $i => $flight)
                @php($tints = ['bg-sky', 'bg-mint', 'bg-lilac', 'bg-peach'])
                <div class="reveal flex flex-col gap-5 rounded-card border border-line/60 {{ $tints[$i % 4] }} p-6 transition-all hover:-translate-y-0.5 hover:shadow-pop"
                     x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ ($i % 4) * 70 }})">
                    <div class="flex items-start justify-between gap-3">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-tile bg-ink text-paper"><x-lucide-plane class="h-6 w-6" /></span>
                        <x-tk.badge :tone="$flight->status->tone()" dot>{{ $flight->status->label() }}</x-tk.badge>
                    </div>
                    <div>
                        <p class="font-display text-xl font-bold text-content">{{ $flight->direction->label() }}</p>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-muted"><x-lucide-calendar class="h-4 w-4" /> {{ $flight->departs_on->translatedFormat('l j F Y') }}</p>
                    </div>
                    <x-tk.whatsapp-button :floating="false" class="w-full" :message="'Bonjour SEZY, je souhaite réserver une place pour le vol '.$flight->direction->label().' du '.$flight->departs_on->format('d/m/Y')" />
                </div>
            @empty
                <div @unless ($next) class="sm:col-span-2" @else class="hidden" @endunless>
                    <x-tk.empty-state icon="plane" title="Aucun vol planifié pour le moment">
                        Écrivez-nous sur WhatsApp pour connaître nos prochaines dates de départ.
                        <x-slot:action>
                            <x-tk.whatsapp-button :floating="false" />
                        </x-slot:action>
                    </x-tk.empty-state>
                </div>
            @endforelse
        </div>
    </x-tk.section>

    {{-- Comment réserver --}}
    <x-tk.section tone="canvas">
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Comment réserver</x-tk.kicker>
            <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Une place pour votre colis en 3 étapes.</h2>
        </div>
        <div class="mt-9 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['n' => '01', 'icon' => 'calendar-check', 'title' => 'Choisissez un départ', 'desc' => 'Repérez le vol qui vous convient dans la liste ci-dessus.'],
                ['n' => '02', 'icon' => 'message-circle', 'title' => 'Réservez la place', 'desc' => 'Un message WhatsApp suffit. On confirme la disponibilité.'],
                ['n' => '03', 'icon' => 'package-check', 'title' => 'Déposez & suivez', 'desc' => 'Déposez votre colis, recevez votre référence, suivez chaque étape.'],
            ] as $i => $step)
                <div class="reveal rounded-card border border-line/60 bg-surface p-6" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 90 }})">
                    <div class="flex items-center justify-between">
                        <span class="grid h-11 w-11 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$step['icon']" class="h-5 w-5" /></span>
                        <span class="font-display text-4xl font-black text-content/10">{{ $step['n'] }}</span>
                    </div>
                    <h3 class="mt-4 font-display text-lg font-bold text-content">{{ $step['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-muted">{{ $step['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </x-tk.section>
</div>
