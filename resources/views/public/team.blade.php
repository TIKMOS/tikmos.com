@php($members = \App\Models\TeamMember::active()->get())
@php($seo = app(\App\Settings\SeoSettings::class))
@php($tints = ['bg-indigo-50', 'bg-gold-100', 'bg-sand', 'bg-mist'])

<x-layouts.public>
    <x-slot:head>
        <x-tk.seo title="L'équipe" description="Une équipe d'experts derrière chaque projet : data, IA, développement et accompagnement." />
        @push('meta')
            <script type="application/ld+json">{!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Organization',
                'name' => $seo->organization_name,
                'url' => url('/'),
                'employee' => $members->map(fn ($m) => ['@type' => 'Person', 'name' => $m->name, 'jobTitle' => $m->role_title])->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endpush
    </x-slot:head>

    {{-- Hero --}}
    <x-tk.section>
        <div class="mx-auto max-w-2xl text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker class="justify-center">L'équipe</x-tk.kicker>
            <div class="mt-3 flex justify-center"><x-tk.divider-gold /></div>
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Une équipe derrière chaque projet.</h1>
            <p class="mx-auto mt-5 max-w-lg text-lg text-muted">Une équipe dévouée à vos projets, du cadrage à la livraison.</p>
        </div>

        @if ($members->isEmpty())
            <div class="mt-10"><x-tk.empty-state icon="users" title="Bientôt">Notre équipe se présentera ici très vite.</x-tk.empty-state></div>
        @else
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($members as $i => $member)
                    <div class="reveal" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ ($i % 3) * 80 }})">
                        <x-tk.team-card :member="$member" :tint="$tints[$i % 4]" />
                    </div>
                @endforeach
            </div>
        @endif
    </x-tk.section>

    {{-- Culture / valeurs --}}
    <x-tk.section tone="canvas">
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Notre culture</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Rigueur, exécution, et le sens du détail.</h2>
        </div>
        <div class="mt-10 grid gap-5 sm:grid-cols-3">
            @foreach ([
                ['icon' => 'target', 'title' => 'Rigueur', 'desc' => 'La précision des mathématiques sur chaque livrable.'],
                ['icon' => 'rocket', 'title' => 'Exécution', 'desc' => 'On ne s\'arrête pas à l\'idée : on livre et on accompagne.'],
                ['icon' => 'users', 'title' => 'Collectif', 'desc' => 'Des expertises complémentaires, au service de votre projet.'],
            ] as $i => $value)
                <div class="rounded-card border border-line/60 {{ ['bg-indigo-50', 'bg-gold-100', 'bg-sand'][$i] }} p-6">
                    <span class="grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$value['icon']" class="h-6 w-6" /></span>
                    <h3 class="mt-4 font-display text-lg font-bold text-content">{{ $value['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-muted">{{ $value['desc'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <x-tk.button href="{{ url('/contact') }}" variant="primary" size="lg" icon="message-circle">Travailler avec nous</x-tk.button>
            <x-tk.button href="{{ url('/a-propos') }}" variant="gold" size="lg">Notre histoire</x-tk.button>
        </div>
    </x-tk.section>
</x-layouts.public>
