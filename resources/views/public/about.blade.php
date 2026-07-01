<x-layouts.public>
    <x-slot:head>
        <x-tk.seo title="À propos" description="Professeur certifié en mathématiques et ingénieur data science & IA : l'expertise qui exécute, au service de vos projets, vos formations et vos achats." />
    </x-slot:head>

    {{-- Hero --}}
    <section class="bg-paper">
        <div class="mx-auto grid w-full max-w-6xl items-center gap-10 px-5 py-14 sm:px-6 md:py-20 lg:grid-cols-2 lg:px-8">
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <x-tk.kicker>À propos</x-tk.kicker>
                <x-tk.divider-gold class="mt-3" />
                <h1 class="mt-4 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">L'expertise qui exécute.</h1>
                <p class="mt-5 max-w-md text-lg text-muted">Des mathématiques à la data science, de la salle de classe aux projets d'entreprise : une seule exigence, livrer des résultats concrets.</p>
                <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-muted">
                    <span class="inline-flex items-center gap-1.5"><x-lucide-graduation-cap class="h-4 w-4 text-gold-600" /> Professeur certifié</span>
                    <span class="text-tk-300">·</span>
                    <span class="inline-flex items-center gap-1.5"><x-lucide-cpu class="h-4 w-4 text-gold-600" /> Ingénieur Data / IA</span>
                </div>
            </div>
            <div class="reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
                <div class="aspect-[5/4] overflow-hidden rounded-[28px] border border-line bg-surface shadow-lg">
                    <img src="{{ asset('img/about-illustration.svg') }}" alt="Illustration — mathématiques, data science et IA" loading="eager" class="h-full w-full object-cover" />
                </div>
            </div>
        </div>
    </section>

    {{-- Histoire --}}
    <x-tk.section tone="canvas">
        <div class="mx-auto max-w-3xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Mon parcours</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <div class="mt-5 space-y-5 text-lg leading-relaxed text-content/80">
                <p>Je suis <strong class="text-content">Moïse TIKO</strong>, fondateur de Tikmos. Tout commence par les <strong class="text-content">mathématiques</strong> : la rigueur, la preuve, l'art de décomposer un problème complexe en étapes claires. Cet état d'esprit, je l'ai porté de l'enseignement vers l'<strong class="text-content">ingénierie data &amp; IA</strong>.</p>
                <p>Aujourd'hui, je conçois et pilote des <strong class="text-content">solutions informatiques sur mesure</strong>, je <strong class="text-content">forme</strong> les équipes, et j'accompagne entreprises comme particuliers — installation, gestion administrative, orientation. En parallèle, je sélectionne des <strong class="text-content">chaussures de luxe</strong> pour celles et ceux qui aiment l'exigence du beau.</p>
                <p>Et je ne travaille jamais seul : une <strong class="text-content">équipe</strong> de confiance m'épaule sur chaque projet. Ma promesse : <strong class="text-content">comprendre votre besoin, et le livrer.</strong></p>
            </div>
        </div>
    </x-tk.section>

    {{-- Valeurs --}}
    <x-tk.section>
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Ce qui me guide</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h2 class="mt-3 font-display text-3xl font-extrabold text-content md:text-4xl">Des valeurs simples, tenues.</h2>
        </div>
        @php($tints = ['bg-indigo-50', 'bg-gold-100', 'bg-sand', 'bg-mist'])
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'target', 'title' => 'Rigueur', 'desc' => 'La précision des mathématiques appliquée à chaque livrable.'],
                ['icon' => 'lightbulb', 'title' => 'Pédagogie', 'desc' => "Rendre simple ce qui est complexe — même pour les non-informaticiens."],
                ['icon' => 'rocket', 'title' => 'Exécution', 'desc' => 'On ne s\'arrête pas à l\'idée : on livre, on forme, on accompagne.'],
                ['icon' => 'gem', 'title' => 'Exigence', 'desc' => 'L\'exigence du luxe, dans le service comme dans les produits.'],
            ] as $i => $value)
                <div class="reveal rounded-card border border-line/60 {{ $tints[$i % 4] }} p-6" x-data x-intersect.once="setTimeout(() => $el.classList.add('is-revealed'), {{ $i * 80 }})">
                    <span class="grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper"><x-dynamic-component :component="'lucide-'.$value['icon']" class="h-6 w-6" /></span>
                    <h3 class="mt-4 font-display text-lg font-bold text-content">{{ $value['title'] }}</h3>
                    <p class="mt-1.5 text-sm text-muted">{{ $value['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </x-tk.section>

    {{-- CTA --}}
    <x-tk.section tone="canvas">
        <div class="flex flex-col items-center text-center reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.divider-gold />
            <h2 class="mt-5 font-display text-3xl font-extrabold text-content md:text-4xl">Travaillons ensemble.</h2>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <x-tk.button href="{{ url('/contact') }}" variant="primary" size="lg" icon="file-text">Demander un devis</x-tk.button>
                <x-tk.button href="{{ url('/services') }}" variant="gold" size="lg">Mes services</x-tk.button>
            </div>
        </div>
    </x-tk.section>
</x-layouts.public>
