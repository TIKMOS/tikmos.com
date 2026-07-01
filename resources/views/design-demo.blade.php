<x-layouts.public>
    <x-slot:head>
        <x-tk.seo title="Design system" />
    </x-slot:head>

    {{-- Hero noir massif --}}
    <section class="bg-ink text-paper">
        <div class="mx-auto w-full max-w-6xl px-5 py-20 sm:px-6 md:py-28 lg:px-8">
            <x-tk.kicker tone="accent">Design system · v2</x-tk.kicker>
            <h1 class="mt-4 max-w-2xl font-display text-5xl font-black leading-[0.95] md:text-7xl">L'expertise qui exécute.</h1>
            <p class="mt-5 max-w-lg text-lg text-tk-400">Formation, data/IA et solutions sur mesure. Galerie de composants du design system.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <x-tk.button variant="accent" size="lg" icon="arrow-right" :iconRight="true">Composant primaire</x-tk.button>
                <x-tk.button variant="secondary" size="lg" class="!border-white/30 !text-white hover:!bg-white/10">Composant secondaire</x-tk.button>
            </div>
        </div>
    </section>

    <x-tk.section>
        <x-tk.kicker>Boutons</x-tk.kicker>
        <div class="mt-5 flex flex-wrap items-center gap-3">
            <x-tk.button variant="primary">Primary</x-tk.button>
            <x-tk.button variant="accent" icon="arrow-right" :iconRight="true">Accent</x-tk.button>
            <x-tk.button variant="secondary">Secondary</x-tk.button>
            <x-tk.button variant="ghost">Ghost</x-tk.button>
            <x-tk.button variant="danger">Danger</x-tk.button>
        </div>

        <x-tk.kicker class="mt-12 block">Badges & statuts</x-tk.kicker>
        <div class="mt-5 flex flex-wrap gap-2">
            <x-tk.badge tone="neutral" dot>Brouillon</x-tk.badge>
            <x-tk.badge tone="info" dot>En cours</x-tk.badge>
            <x-tk.badge tone="warning" dot>En attente</x-tk.badge>
            <x-tk.badge tone="success" dot>Terminé</x-tk.badge>
            <x-tk.badge tone="danger" dot>Incident</x-tk.badge>
            <x-tk.badge tone="accent">Accent</x-tk.badge>
        </div>

        <x-tk.kicker class="mt-12 block">Statistiques</x-tk.kicker>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-tk.stat label="Projets ce mois" value="128" icon="folder-kanban" />
            <x-tk.stat label="Devis à traiter" value="14" icon="file-text" tone="accent" />
            <x-tk.stat label="En attente paiement" value="6" icon="wallet" />
            <x-tk.stat label="Prochaine session" value="03/11" icon="calendar" />
        </div>
    </x-tk.section>

    <x-tk.section tone="paper">
        <div class="grid gap-8 lg:grid-cols-2">
            <div>
                <x-tk.kicker>Suivi de devis · stepper</x-tk.kicker>
                <x-tk.card class="mt-5">
                    <p class="mb-5 font-mono text-sm font-bold tracking-wide">TKM-DEV-2026-00427</p>
                    <x-tk.stepper :steps="[
                        ['label' => 'Demande reçue', 'date' => '28/10', 'state' => 'done'],
                        ['label' => 'À l\'étude', 'date' => '01/11', 'state' => 'done'],
                        ['label' => 'Devis envoyé', 'date' => '03/11', 'state' => 'current'],
                        ['label' => 'Accepté', 'state' => 'todo'],
                        ['label' => 'Livré', 'state' => 'todo'],
                    ]" />
                </x-tk.card>
            </div>
            <div class="space-y-6">
                <div>
                    <x-tk.kicker>Formulaire</x-tk.kicker>
                    <div class="mt-5 space-y-4">
                        <x-tk.field label="Référence du devis" hint="Format : TKM-DEV-AAAA-NNNNN" for="ref">
                            <x-tk.input id="ref" icon="search" placeholder="TKM-DEV-2026-00427" />
                        </x-tk.field>
                        <x-tk.field label="Email" for="mail" :error="'Cet email est invalide.'">
                            <x-tk.input id="mail" type="email" :invalid="true" placeholder="vous@exemple.com" />
                        </x-tk.field>
                    </div>
                </div>
                <div>
                    <x-tk.kicker>Chargement · skeleton</x-tk.kicker>
                    <div class="mt-5"><x-tk.skeleton variant="card" /></div>
                </div>
            </div>
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-2">
            <div>
                <x-tk.kicker>État vide</x-tk.kicker>
                <div class="mt-5">
                    <x-tk.empty-state icon="inbox" title="Rien pour l'instant">
                        Vos éléments apparaîtront ici dès votre première action.
                        <x-slot:action>
                            <x-tk.button variant="accent" size="sm" icon="plus">Nouvelle demande</x-tk.button>
                        </x-slot:action>
                    </x-tk.empty-state>
                </div>
            </div>
            <div>
                <x-tk.kicker>Carte noire & avatars</x-tk.kicker>
                <x-tk.card variant="dark" class="mt-5">
                    <div class="flex items-center gap-3">
                        <x-tk.avatar name="Boris Vital" />
                        <div>
                            <p class="font-display font-bold">Boris Vital</p>
                            <p class="text-sm text-tk-400">Fondateur · Tikmos</p>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-tk-300">« On s'occupe de tout. Vous, vous suivez. »</p>
                </x-tk.card>
                <div class="mt-4 flex justify-end">
                    <x-tk.button variant="accent" size="sm"
                        x-data
                        x-on:click="$dispatch('tk-toast', { type: 'success', message: 'Tout fonctionne' })">Tester un toast</x-tk.button>
                </div>
            </div>
        </div>

        {{-- Composants admin V2 (config/CMS) --}}
        <div class="mt-16">
            <x-tk.kicker>V2 · Composants admin</x-tk.kicker>
            <h2 class="mt-2 font-display text-2xl font-bold text-content">Configuration & CMS</h2>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <x-tk.admin-section title="Carte de réglage" description="x-tk.admin-section : en-tête + aide + contenu." icon="settings-2">
                    <div class="space-y-4">
                        <x-tk.toggle label="Bandeau supérieur" description="Afficher le bandeau pays en haut du site." />
                        <x-tk.toggle label="Boutique active" description="Active la boutique et son menu." />
                    </div>
                </x-tk.admin-section>

                <x-tk.admin-section title="Notation & média" description="x-tk.rating (lecture) et x-tk.media-input." icon="star">
                    <div class="space-y-5">
                        <div class="flex items-center gap-3">
                            <x-tk.rating :value="4" readonly />
                            <span class="text-sm text-muted">4 / 5</span>
                        </div>
                        <x-tk.media-input model="demoLogo" label="Logo (aperçu instantané)" hint="PNG/JPG/WebP — 2 Mo max." />
                    </div>
                </x-tk.admin-section>
            </div>
            <p class="mt-4 text-sm text-muted">L'éditeur riche <code class="font-mono text-xs">x-tk.editor</code> (TipTap) se vérifie dans un contexte Livewire (lot Pages légales).</p>
        </div>
    </x-tk.section>
</x-layouts.public>
