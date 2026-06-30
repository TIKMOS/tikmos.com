<?php

use App\Services\MediaService;
use App\Settings\HomepageSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public string $hero_kicker = '';

    public string $hero_title = '';

    public string $hero_subtitle = '';

    public ?string $hero_image = null;

    public $heroImageFile = null;

    public array $trust_badges = [];

    public array $services = [];

    public array $steps = [];

    public string $cta_title = '';

    public string $cta_text = '';

    public array $sections_enabled = [];

    public array $sections_order = [];

    /** @var array<string, string> */
    public array $sectionLabels = [
        'services' => 'Services',
        'steps' => 'Comment ça marche',
        'reviews' => 'Témoignages',
        'faq' => 'FAQ',
        'partners' => 'Partenaires',
        'marquee' => 'Bandeau marques',
    ];

    public function mount(HomepageSettings $s): void
    {
        $this->hero_kicker = $s->hero_kicker;
        $this->hero_title = $s->hero_title;
        $this->hero_subtitle = $s->hero_subtitle;
        $this->hero_image = $s->hero_image;
        $this->trust_badges = $s->trust_badges;
        $this->services = $s->services;
        $this->steps = $s->steps;
        $this->cta_title = $s->cta_title;
        $this->cta_text = $s->cta_text;
        $this->sections_enabled = $s->sections_enabled;
        $this->sections_order = $s->sections_order;
    }

    public function addBadge(): void
    {
        $this->trust_badges[] = ['icon' => 'check', 'label' => ''];
    }

    public function removeBadge(int $i): void
    {
        unset($this->trust_badges[$i]);
        $this->trust_badges = array_values($this->trust_badges);
    }

    public function addStep(): void
    {
        $this->steps[] = ['icon' => 'circle', 'title' => '', 'desc' => ''];
    }

    public function removeStep(int $i): void
    {
        unset($this->steps[$i]);
        $this->steps = array_values($this->steps);
    }

    /** @param  array<int, string>  $ids */
    public function reorderSections(array $ids): void
    {
        $this->sections_order = array_values($ids);
    }

    public function removeHeroImage(MediaService $media): void
    {
        $media->delete($this->hero_image);
        $this->hero_image = null;
        $this->heroImageFile = null;
    }

    public function save(MediaService $media): void
    {
        $this->validate([
            'hero_kicker' => ['required', 'string', 'max:80'],
            'hero_title' => ['required', 'string', 'max:120'],
            'hero_subtitle' => ['required', 'string', 'max:300'],
            'heroImageFile' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'trust_badges' => ['array'],
            'trust_badges.*.icon' => ['required', 'string', 'max:40'],
            'trust_badges.*.label' => ['required', 'string', 'max:40'],
            'services' => ['array'],
            'services.*.title' => ['required', 'string', 'max:60'],
            'services.*.desc' => ['required', 'string', 'max:160'],
            'steps' => ['array'],
            'steps.*.title' => ['required', 'string', 'max:60'],
            'steps.*.desc' => ['required', 'string', 'max:200'],
            'cta_title' => ['required', 'string', 'max:120'],
            'cta_text' => ['required', 'string', 'max:200'],
        ]);

        $s = app(HomepageSettings::class);

        if ($this->heroImageFile) {
            $this->hero_image = $media->replace($this->hero_image, $this->heroImageFile, 'homepage');
        }

        $s->hero_kicker = $this->hero_kicker;
        $s->hero_title = $this->hero_title;
        $s->hero_subtitle = $this->hero_subtitle;
        $s->hero_image = $this->hero_image;
        $s->trust_badges = $this->trust_badges;
        $s->services = $this->services;
        $s->steps = $this->steps;
        $s->cta_title = $this->cta_title;
        $s->cta_text = $this->cta_text;
        $s->sections_enabled = $this->sections_enabled;
        $s->sections_order = $this->sections_order;
        $s->save();

        $this->heroImageFile = null;
        $this->dispatch('sz-toast', type: 'success', message: 'Page d\'accueil enregistrée.');
    }

    public function with(): array
    {
        return [
            'heroPreview' => $this->hero_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->hero_image) : null,
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Accueil</h1>
        </div>
        <div class="flex items-center gap-2">
            <x-tk.button href="{{ url('/') }}" variant="secondary" icon="external-link">Voir le site</x-tk.button>
            <x-tk.button wire:click="save" variant="primary" icon="check">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save">…</span>
            </x-tk.button>
        </div>
    </div>

    <form wire:submit="save" class="mt-8 space-y-5">
        {{-- Hero --}}
        <x-tk.admin-section title="Hero" description="Le haut de page : sur-titre, titre, sous-titre, image." icon="layout-template">
            <div class="space-y-4">
                <x-tk.field label="Sur-titre" :error="$errors->first('hero_kicker')"><x-tk.input wire:model="hero_kicker" /></x-tk.field>
                <x-tk.field label="Titre" :error="$errors->first('hero_title')"><x-tk.input wire:model="hero_title" /></x-tk.field>
                <x-tk.field label="Sous-titre" :error="$errors->first('hero_subtitle')">
                    <textarea wire:model="hero_subtitle" rows="2" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
                </x-tk.field>
                <x-tk.media-input model="heroImageFile" :preview="$heroPreview" label="Image du hero" remove="removeHeroImage" hint="Laisser vide pour l'image par défaut. PNG/JPG/WebP — 2 Mo." />
            </div>
        </x-tk.admin-section>

        {{-- Badges de confiance --}}
        <x-tk.admin-section title="Badges de confiance" description="Petits arguments sous le hero (icône Lucide + texte)." icon="badge-check">
            <div class="space-y-2">
                @foreach ($trust_badges as $i => $badge)
                    <div class="flex items-center gap-2" wire:key="badge-{{ $i }}">
                        <x-tk.input wire:model="trust_badges.{{ $i }}.icon" placeholder="icône (ex. shield-check)" class="sm:w-56" />
                        <x-tk.input wire:model="trust_badges.{{ $i }}.label" placeholder="Texte" class="flex-1" />
                        <button type="button" wire:click="removeBadge({{ $i }})" class="grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addBadge" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter un badge</button>
        </x-tk.admin-section>

        {{-- Services --}}
        <x-tk.admin-section title="Services (4 cartes)" description="Les cartes de services. Icône Lucide, titre, description, lien." icon="grid-3x3">
            <div class="space-y-3">
                @foreach ($services as $i => $service)
                    <div class="rounded-card border border-line p-3" wire:key="service-{{ $i }}">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <x-tk.input wire:model="services.{{ $i }}.title" placeholder="Titre" />
                            <x-tk.input wire:model="services.{{ $i }}.icon" placeholder="icône (ex. package)" />
                        </div>
                        <div class="mt-2"><x-tk.input wire:model="services.{{ $i }}.desc" placeholder="Description" /></div>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            <x-tk.input wire:model="services.{{ $i }}.href" placeholder="Lien (ex. /achat)" />
                            <label class="flex items-center gap-2 text-sm text-content"><input type="checkbox" wire:model="services.{{ $i }}.soon" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20"> Marquer « Bientôt »</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-tk.admin-section>

        {{-- Étapes --}}
        <x-tk.admin-section title="Comment ça marche" description="Les étapes (numérotées automatiquement)." icon="list-ordered">
            <div class="space-y-2">
                @foreach ($steps as $i => $step)
                    <div class="flex items-start gap-2" wire:key="step-{{ $i }}">
                        <span class="mt-2.5 font-mono text-sm text-tk-400">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="flex-1 space-y-2">
                            <x-tk.input wire:model="steps.{{ $i }}.title" placeholder="Titre de l'étape" />
                            <x-tk.input wire:model="steps.{{ $i }}.desc" placeholder="Description" />
                        </div>
                        <button type="button" wire:click="removeStep({{ $i }})" class="mt-1 grid h-10 w-10 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
            <button type="button" wire:click="addStep" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-content hover:underline"><x-lucide-plus class="h-4 w-4" /> Ajouter une étape</button>
        </x-tk.admin-section>

        {{-- CTA --}}
        <x-tk.admin-section title="Appel à l'action (bas de page)" icon="megaphone">
            <div class="space-y-4">
                <x-tk.field label="Titre" :error="$errors->first('cta_title')"><x-tk.input wire:model="cta_title" /></x-tk.field>
                <x-tk.field label="Texte" :error="$errors->first('cta_text')"><x-tk.input wire:model="cta_text" /></x-tk.field>
            </div>
        </x-tk.admin-section>

        {{-- Sections --}}
        <x-tk.admin-section title="Sections de la page" description="Activez/désactivez et réordonnez (glisser) les blocs." icon="layout-list">
            <x-tk.sortable-list onReorder="reorderSections">
                @foreach ($sections_order as $key)
                    <li wire:key="sec-{{ $key }}" data-id="{{ $key }}" class="flex items-center gap-3 rounded-card border border-line bg-surface p-3">
                        <x-lucide-grip-vertical class="h-5 w-5 shrink-0 cursor-grab text-tk-300" />
                        <span class="flex-1 font-medium text-content">{{ $sectionLabels[$key] ?? ucfirst($key) }}</span>
                        <x-tk.toggle wire:model="sections_enabled.{{ $key }}" />
                    </li>
                @endforeach
            </x-tk.sortable-list>
            <p class="mt-2 text-xs text-muted">Le bandeau marques se règle via l'interrupteur « Bandeau marques ».</p>
            <div class="mt-3">
                <x-tk.toggle label="Bandeau marques" description="La bande défilante des sites préférés." wire:model="sections_enabled.marquee" />
            </div>
        </x-tk.admin-section>

        <div class="flex justify-end">
            <x-tk.button type="submit" variant="primary" icon="check">Enregistrer l'accueil</x-tk.button>
        </div>
    </form>
</div>
