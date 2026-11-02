<?php

use App\Models\AboutParagraph;
use App\Models\AboutValue;
use App\Services\MediaService;
use App\Settings\AboutSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $person_name = '';

    public string $hero_kicker = '';

    public string $subtitle = '';

    public string $professor_label = '';

    public string $specialty_label = '';

    public ?string $image = null;

    public string $image_alt = '';

    public $imageFile = null;

    public ?string $og_image = null;

    public $ogImageFile = null;

    public string $linkedin_url = '';

    public string $linkedin_label = '';

    public string $story_kicker = '';

    public string $values_kicker = '';

    public string $values_title = '';

    public string $cta_title = '';

    public string $quote_label = '';

    public string $quote_url = '';

    public string $services_label = '';

    public string $services_url = '';

    public bool $show_hero = true;

    public bool $show_story = true;

    public bool $show_values = true;

    public bool $show_cta = true;

    public bool $show_linkedin = true;

    public string $seo_title = '';

    public string $seo_description = '';

    /** @var array<int, array{id: int|null, body: string, sort_order: int}> */
    public array $paragraphs = [];

    /** @var array<int, array{id: int|null, title: string, description: string, icon: string, sort_order: int, is_visible: bool}> */
    public array $values = [];

    private const ICONS = ['target', 'lightbulb', 'rocket', 'gem', 'book-open', 'brain', 'check', 'heart'];

    public function mount(AboutSettings $settings): void
    {
        $this->ensureCanManage();

        foreach (['title', 'person_name', 'hero_kicker', 'subtitle', 'professor_label', 'specialty_label', 'image_alt', 'linkedin_url', 'linkedin_label', 'story_kicker', 'values_kicker', 'values_title', 'cta_title', 'quote_label', 'quote_url', 'services_label', 'services_url', 'show_hero', 'show_story', 'show_values', 'show_cta', 'show_linkedin', 'seo_title', 'seo_description', 'image', 'og_image'] as $property) {
            $this->{$property} = $settings->{$property};
        }

        $this->paragraphs = AboutParagraph::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (AboutParagraph $paragraph): array => ['id' => $paragraph->id, 'body' => $paragraph->body, 'sort_order' => $paragraph->sort_order])
            ->all();

        $this->values = AboutValue::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (AboutValue $value): array => ['id' => $value->id, 'title' => $value->title, 'description' => $value->description, 'icon' => $value->icon, 'sort_order' => $value->sort_order, 'is_visible' => $value->is_visible])
            ->all();
    }

    public function addParagraph(): void
    {
        $this->ensureCanManage();
        $this->paragraphs[] = ['id' => null, 'body' => '', 'sort_order' => count($this->paragraphs) + 1];
    }

    public function removeParagraph(int $index): void
    {
        $this->ensureCanManage();
        unset($this->paragraphs[$index]);
        $this->paragraphs = array_values($this->paragraphs);
    }

    public function addValue(): void
    {
        $this->ensureCanManage();
        $this->values[] = ['id' => null, 'title' => '', 'description' => '', 'icon' => 'target', 'sort_order' => count($this->values) + 1, 'is_visible' => true];
    }

    public function save(MediaService $media): void
    {
        $this->ensureCanManage();

        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'person_name' => ['required', 'string', 'max:120'],
            'hero_kicker' => ['required', 'string', 'max:80'],
            'subtitle' => ['required', 'string', 'max:500'],
            'professor_label' => ['required', 'string', 'max:80'],
            'specialty_label' => ['required', 'string', 'max:80'],
            'image_alt' => ['required', 'string', 'max:180'],
            'imageFile' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'ogImageFile' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'linkedin_url' => ['required', 'string', 'max:2048', 'url:https'],
            'linkedin_label' => ['required', 'string', 'max:100'],
            'story_kicker' => ['required', 'string', 'max:80'],
            'values_kicker' => ['required', 'string', 'max:80'],
            'values_title' => ['required', 'string', 'max:140'],
            'cta_title' => ['required', 'string', 'max:140'],
            'quote_label' => ['required', 'string', 'max:80'],
            'quote_url' => ['required', 'string', 'max:2048', $this->safeDestinationRule()],
            'services_label' => ['required', 'string', 'max:80'],
            'services_url' => ['required', 'string', 'max:2048', $this->safeDestinationRule()],
            'show_hero' => ['boolean'],
            'show_story' => ['boolean'],
            'show_values' => ['boolean'],
            'show_cta' => ['boolean'],
            'show_linkedin' => ['boolean'],
            'seo_title' => ['required', 'string', 'max:70'],
            'seo_description' => ['required', 'string', 'max:180'],
            'paragraphs' => ['required', 'array', 'min:1', 'max:20'],
            'paragraphs.*.id' => ['nullable', 'integer', 'exists:about_paragraphs,id'],
            'paragraphs.*.body' => ['required', 'string', 'max:4000'],
            'paragraphs.*.sort_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'values' => ['required', 'array', 'min:1', 'max:20'],
            'values.*.id' => ['nullable', 'integer', 'exists:about_values,id'],
            'values.*.title' => ['required', 'string', 'max:100'],
            'values.*.description' => ['required', 'string', 'max:500'],
            'values.*.icon' => ['required', 'string', 'in:'.implode(',', self::ICONS)],
            'values.*.sort_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'values.*.is_visible' => ['required', 'boolean'],
        ]);

        $settings = app(AboutSettings::class);
        $oldImages = [$settings->image, $settings->og_image];
        $newImages = [];

        try {
            if ($this->imageFile) {
                $newImages['image'] = $media->storeImage($this->imageFile, 'about');
                $data['image'] = $newImages['image'];
            }

            if ($this->ogImageFile) {
                $newImages['og_image'] = $media->storeImage($this->ogImageFile, 'about/og');
                $data['og_image'] = $newImages['og_image'];
            }

            DB::transaction(function () use ($settings, $data): void {
                foreach (['title', 'person_name', 'hero_kicker', 'subtitle', 'professor_label', 'specialty_label', 'image_alt', 'linkedin_url', 'linkedin_label', 'story_kicker', 'values_kicker', 'values_title', 'cta_title', 'quote_label', 'quote_url', 'services_label', 'services_url', 'show_hero', 'show_story', 'show_values', 'show_cta', 'show_linkedin', 'seo_title', 'seo_description', 'image', 'og_image'] as $property) {
                    if (array_key_exists($property, $data)) {
                        $settings->{$property} = $data[$property];
                    }
                }

                $settings->save();

                $paragraphIds = collect($data['paragraphs'])->pluck('id')->filter()->map(fn ($id): int => (int) $id)->all();
                AboutParagraph::query()->when($paragraphIds !== [], fn ($query) => $query->whereNotIn('id', $paragraphIds))->when($paragraphIds === [], fn ($query) => $query)->delete();

                foreach ($data['paragraphs'] as $paragraph) {
                    $model = isset($paragraph['id']) ? AboutParagraph::query()->findOrFail($paragraph['id']) : new AboutParagraph(['key' => 'about-'.Str::uuid()]);
                    $model->body = $paragraph['body'];
                    $model->sort_order = (int) $paragraph['sort_order'];
                    $model->save();
                }

                foreach ($data['values'] as $value) {
                    $model = isset($value['id']) ? AboutValue::query()->findOrFail($value['id']) : new AboutValue(['key' => Str::slug($value['title']).'-'.Str::lower(Str::random(8))]);
                    $model->fill([
                        'title' => $value['title'],
                        'description' => $value['description'],
                        'icon' => $value['icon'],
                        'sort_order' => (int) $value['sort_order'],
                        'is_visible' => (bool) $value['is_visible'],
                    ])->save();
                }
            });
        } catch (Throwable $exception) {
            foreach ($newImages as $path) {
                $media->delete($path);
            }

            throw $exception;
        }

        foreach (['image', 'og_image'] as $field) {
            if (isset($newImages[$field]) && $oldImages[$field === 'image' ? 0 : 1] !== $newImages[$field]) {
                $media->delete($oldImages[$field === 'image' ? 0 : 1]);
            }
        }

        $this->image = $settings->image;
        $this->og_image = $settings->og_image;
        $this->imageFile = null;
        $this->ogImageFile = null;
        $this->dispatch('tk-toast', type: 'success', message: 'La page À propos a été enregistrée et publiée.');
    }

    public function removeImage(MediaService $media): void
    {
        $this->ensureCanManage();
        $settings = app(AboutSettings::class);
        $oldImage = $settings->image;
        $settings->image = null;
        $settings->save();
        $media->delete($oldImage);
        $this->image = null;
        $this->imageFile = null;
        $this->dispatch('tk-toast', type: 'success', message: 'La photo est retirée. L’illustration par défaut est rétablie.');
    }

    public function removeOgImage(MediaService $media): void
    {
        $this->ensureCanManage();
        $settings = app(AboutSettings::class);
        $oldImage = $settings->og_image;
        $settings->og_image = null;
        $settings->save();
        $media->delete($oldImage);
        $this->og_image = null;
        $this->ogImageFile = null;
        $this->dispatch('tk-toast', type: 'success', message: 'L’image de partage dédiée est retirée.');
    }

    public function with(): array
    {
        return [
            'imagePreview' => $this->image ? Storage::disk('public')->url($this->image) : asset('img/about-illustration.svg'),
            'ogPreview' => $this->og_image ? Storage::disk('public')->url($this->og_image) : null,
            'allowedIcons' => self::ICONS,
        ];
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()?->can('cms.pages.manage'), 403);
    }

    private function safeDestinationRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || preg_match('/[\x00-\x20\\\\]/', $value)) {
                $fail('Saisissez un chemin interne ou une adresse HTTPS valide.');

                return;
            }

            if (str_starts_with($value, '/') && ! str_starts_with($value, '//') && ! str_starts_with($value, '/\\')) {
                return;
            }

            $parts = parse_url($value);
            if (filter_var($value, FILTER_VALIDATE_URL) && is_array($parts) && strtolower($parts['scheme'] ?? '') === 'https' && ! empty($parts['host'])) {
                return;
            }

            $fail('Saisissez un chemin interne ou une adresse HTTPS valide.');
        };
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">À propos</h1>
            <p class="mt-1 text-sm text-muted">Modifiez les informations affichées publiquement. Chaque enregistrement est publié immédiatement.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-tk.button href="{{ route('about') }}" variant="secondary" icon="external-link">Voir la page</x-tk.button>
            <x-tk.button wire:click="save" variant="primary" icon="check">
                <span wire:loading.remove wire:target="save">Enregistrer et publier</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </x-tk.button>
        </div>
    </div>

    <form wire:submit="save" class="mt-8 space-y-5">
        <x-tk.admin-section title="Introduction" description="Titre, accroche et mentions professionnelles du bloc principal." icon="text">
            <div class="grid gap-4 md:grid-cols-2">
                <x-tk.field label="Titre principal" for="about-title" :error="$errors->first('title')"><x-tk.input id="about-title" wire:model="title" maxlength="120" required /></x-tk.field>
                <x-tk.field label="Nom affiché" for="about-person-name" :error="$errors->first('person_name')"><x-tk.input id="about-person-name" wire:model="person_name" maxlength="120" required /></x-tk.field>
                <x-tk.field label="Petit titre" for="about-hero-kicker" :error="$errors->first('hero_kicker')"><x-tk.input id="about-hero-kicker" wire:model="hero_kicker" maxlength="80" required /></x-tk.field>
                <x-tk.field label="Mention professeur" for="about-professor" :error="$errors->first('professor_label')"><x-tk.input id="about-professor" wire:model="professor_label" maxlength="80" required /></x-tk.field>
                <x-tk.field label="Accroche" for="about-subtitle" :error="$errors->first('subtitle')" class="md:col-span-2"><textarea id="about-subtitle" wire:model="subtitle" rows="3" maxlength="500" required aria-invalid="{{ $errors->has('subtitle') ? 'true' : 'false' }}" @if ($errors->has('subtitle')) aria-describedby="about-subtitle-error" @endif class="w-full rounded-input border border-control-line bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea></x-tk.field>
                <x-tk.field label="Mention métier" for="about-specialty" :error="$errors->first('specialty_label')"><x-tk.input id="about-specialty" wire:model="specialty_label" maxlength="80" required /></x-tk.field>
                <x-tk.field label="Texte du lien LinkedIn" for="about-linkedin-label" :error="$errors->first('linkedin_label')"><x-tk.input id="about-linkedin-label" wire:model="linkedin_label" maxlength="100" required /></x-tk.field>
                <x-tk.field label="URL LinkedIn (HTTPS)" for="about-linkedin-url" :error="$errors->first('linkedin_url')" class="md:col-span-2"><x-tk.input id="about-linkedin-url" type="url" wire:model="linkedin_url" maxlength="2048" required /></x-tk.field>
                <x-tk.toggle label="Afficher l’introduction" description="Masquer le hero laisse le reste de la page visible." wire:model="show_hero" />
                <x-tk.toggle label="Afficher le lien LinkedIn" wire:model="show_linkedin" />
            </div>
        </x-tk.admin-section>

        <x-tk.admin-section title="Photo de présentation" description="PNG, JPG ou WebP — 2 Mo maximum. L’ancienne image reste active si l’enregistrement échoue." icon="image">
            <x-tk.media-input model="imageFile" :preview="$imagePreview" label="Photo de la page À propos" alt="image_alt" remove="removeImage" hint="L’illustration actuelle reste affichée tant qu’aucun remplacement n’est enregistré." />
            @error('image_alt')<p class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
        </x-tk.admin-section>

        <x-tk.admin-section title="Mon parcours" description="Les paragraphes sont du texte simple, échappé à l’affichage." icon="book-open">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-tk.field label="Petit titre de section" for="about-story-kicker" :error="$errors->first('story_kicker')"><x-tk.input id="about-story-kicker" wire:model="story_kicker" maxlength="80" required /></x-tk.field>
                <x-tk.toggle label="Afficher le parcours" wire:model="show_story" />
            </div>
            <div class="mt-5 space-y-4">
                @foreach ($paragraphs as $index => $paragraph)
                    <div wire:key="about-paragraph-{{ $paragraph['id'] ?? 'new-'.$index }}" class="rounded-card border border-line p-4">
                        <div class="mb-3 flex items-center justify-between"><h3 class="font-semibold text-content">Paragraphe {{ $index + 1 }}</h3><button type="button" wire:click="removeParagraph({{ $index }})" class="min-h-11 rounded-btn px-3 text-sm font-medium text-danger hover:bg-danger/5">Supprimer</button></div>
                        <x-tk.field label="Texte" for="about-paragraph-{{ $index }}" :error="$errors->first('paragraphs.'.$index.'.body')"><textarea id="about-paragraph-{{ $index }}" wire:model="paragraphs.{{ $index }}.body" rows="4" maxlength="4000" required aria-invalid="{{ $errors->has('paragraphs.'.$index.'.body') ? 'true' : 'false' }}" @if ($errors->has('paragraphs.'.$index.'.body')) aria-describedby="about-paragraph-{{ $index }}-error" @endif class="w-full rounded-input border border-control-line bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea></x-tk.field>
                        <div class="mt-3 max-w-48"><x-tk.field label="Ordre" for="about-paragraph-order-{{ $index }}" :error="$errors->first('paragraphs.'.$index.'.sort_order')"><x-tk.input id="about-paragraph-order-{{ $index }}" type="number" min="1" max="9999" wire:model="paragraphs.{{ $index }}.sort_order" required /></x-tk.field></div>
                        <input type="hidden" wire:model="paragraphs.{{ $index }}.id">
                    </div>
                @endforeach
                @error('paragraphs')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                <x-tk.button type="button" wire:click="addParagraph" variant="secondary" icon="plus">Ajouter un paragraphe</x-tk.button>
            </div>
        </x-tk.admin-section>

        <x-tk.admin-section title="Valeurs" description="Les valeurs masquées sont conservées et peuvent être réactivées." icon="sparkles">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-tk.field label="Petit titre de section" for="about-values-kicker" :error="$errors->first('values_kicker')"><x-tk.input id="about-values-kicker" wire:model="values_kicker" maxlength="80" required /></x-tk.field>
                <x-tk.field label="Titre de section" for="about-values-title" :error="$errors->first('values_title')"><x-tk.input id="about-values-title" wire:model="values_title" maxlength="140" required /></x-tk.field>
                <x-tk.toggle label="Afficher les valeurs" wire:model="show_values" />
            </div>
            <div class="mt-5 space-y-4">
                @foreach ($values as $index => $value)
                    <div wire:key="about-value-{{ $value['id'] ?? 'new-'.$index }}" class="rounded-card border border-line p-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-tk.field label="Valeur" for="about-value-title-{{ $index }}" :error="$errors->first('values.'.$index.'.title')"><x-tk.input id="about-value-title-{{ $index }}" wire:model="values.{{ $index }}.title" maxlength="100" required /></x-tk.field>
                            <x-tk.field label="Icône" for="about-value-icon-{{ $index }}" :error="$errors->first('values.'.$index.'.icon')"><select id="about-value-icon-{{ $index }}" wire:model="values.{{ $index }}.icon" class="w-full rounded-input border border-control-line bg-surface px-4 py-3 text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">@foreach ($allowedIcons as $icon)<option value="{{ $icon }}">{{ ucfirst($icon) }}</option>@endforeach</select></x-tk.field>
                            <x-tk.field label="Description" for="about-value-description-{{ $index }}" :error="$errors->first('values.'.$index.'.description')" class="sm:col-span-2"><textarea id="about-value-description-{{ $index }}" wire:model="values.{{ $index }}.description" rows="2" maxlength="500" required aria-invalid="{{ $errors->has('values.'.$index.'.description') ? 'true' : 'false' }}" @if ($errors->has('values.'.$index.'.description')) aria-describedby="about-value-description-{{ $index }}-error" @endif class="w-full rounded-input border border-control-line bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea></x-tk.field>
                            <x-tk.field label="Ordre" for="about-value-order-{{ $index }}" :error="$errors->first('values.'.$index.'.sort_order')"><x-tk.input id="about-value-order-{{ $index }}" type="number" min="1" max="9999" wire:model="values.{{ $index }}.sort_order" required /></x-tk.field>
                            <x-tk.toggle label="Visible sur la page" wire:model="values.{{ $index }}.is_visible" />
                        </div>
                        <input type="hidden" wire:model="values.{{ $index }}.id">
                    </div>
                @endforeach
                @error('values')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                <x-tk.button type="button" wire:click="addValue" variant="secondary" icon="plus">Ajouter une valeur</x-tk.button>
            </div>
        </x-tk.admin-section>

        <x-tk.admin-section title="Appel à l’action" description="Les destinations acceptent les chemins internes ou des liens HTTPS." icon="mouse-pointer-click">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-tk.field label="Titre" for="about-cta-title" :error="$errors->first('cta_title')"><x-tk.input id="about-cta-title" wire:model="cta_title" maxlength="140" required /></x-tk.field>
                <x-tk.toggle label="Afficher les appels à l’action" wire:model="show_cta" />
                <x-tk.field label="Bouton devis" for="about-quote-label" :error="$errors->first('quote_label')"><x-tk.input id="about-quote-label" wire:model="quote_label" maxlength="80" required /></x-tk.field>
                <x-tk.field label="Destination devis" for="about-quote-url" :error="$errors->first('quote_url')"><x-tk.input id="about-quote-url" wire:model="quote_url" maxlength="2048" required /></x-tk.field>
                <x-tk.field label="Bouton services" for="about-services-label" :error="$errors->first('services_label')"><x-tk.input id="about-services-label" wire:model="services_label" maxlength="80" required /></x-tk.field>
                <x-tk.field label="Destination services" for="about-services-url" :error="$errors->first('services_url')"><x-tk.input id="about-services-url" wire:model="services_url" maxlength="2048" required /></x-tk.field>
            </div>
        </x-tk.admin-section>

        <x-tk.admin-section title="Référencement" description="Ces informations alimentent le titre, la description et le partage social de cette page." icon="search-check">
            <div class="grid gap-4">
                <x-tk.field label="Meta title" for="about-seo-title" :error="$errors->first('seo_title')"><x-tk.input id="about-seo-title" wire:model="seo_title" maxlength="70" required /></x-tk.field>
                <x-tk.field label="Meta description" for="about-seo-description" :error="$errors->first('seo_description')"><textarea id="about-seo-description" wire:model="seo_description" rows="3" maxlength="180" required aria-invalid="{{ $errors->has('seo_description') ? 'true' : 'false' }}" @if ($errors->has('seo_description')) aria-describedby="about-seo-description-error" @endif class="w-full rounded-input border border-control-line bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea></x-tk.field>
                <x-tk.media-input model="ogImageFile" :preview="$ogPreview" label="Image de partage social (facultatif)" remove="removeOgImage" hint="PNG, JPG ou WebP — 2 Mo maximum. À défaut, l’image centrale du site est utilisée." />
            </div>
        </x-tk.admin-section>

        <div class="flex justify-end"><x-tk.button type="submit" variant="primary" icon="check"><span wire:loading.remove wire:target="save">Enregistrer et publier</span><span wire:loading wire:target="save">Enregistrement…</span></x-tk.button></div>
    </form>
</div>
