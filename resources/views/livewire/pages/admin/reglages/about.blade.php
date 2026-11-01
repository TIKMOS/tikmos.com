<?php

use App\Services\MediaService;
use App\Settings\AboutSettings;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public ?string $image = null;

    public $imageFile = null;

    public function mount(AboutSettings $settings): void
    {
        $this->image = $settings->image;
    }

    public function save(MediaService $media): void
    {
        $this->validate([
            'imageFile' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $settings = app(AboutSettings::class);

        if ($this->imageFile) {
            $this->image = $media->replace($settings->image, $this->imageFile, 'about');
        }

        $settings->image = $this->image;
        $settings->save();

        $this->imageFile = null;
        $this->dispatch('tk-toast', type: 'success', message: 'Photo de la page À propos enregistrée.');
    }

    public function removeImage(MediaService $media): void
    {
        $settings = app(AboutSettings::class);
        $media->delete($settings->image);
        $settings->image = null;
        $settings->save();

        $this->image = null;
        $this->imageFile = null;
        $this->dispatch('tk-toast', type: 'success', message: 'Photo retirée. L’illustration par défaut est rétablie.');
    }

    public function with(): array
    {
        return [
            'imagePreview' => $this->image ? Storage::disk('public')->url($this->image) : asset('img/about-illustration.svg'),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">À propos</h1>
            <p class="mt-1 text-sm text-muted">Gérez l’image affichée sur la page publique À propos.</p>
        </div>
        <div class="flex items-center gap-2">
            <x-tk.button href="{{ url('/a-propos') }}" variant="secondary" icon="external-link">Voir la page</x-tk.button>
            <x-tk.button wire:click="save" variant="primary" icon="check">
                <span wire:loading.remove wire:target="save">Enregistrer</span>
                <span wire:loading wire:target="save">Enregistrement…</span>
            </x-tk.button>
        </div>
    </div>

    <form wire:submit="save" class="mt-8 space-y-5">
        <x-tk.admin-section title="Photo de présentation" description="Cette image apparaît dans le bloc principal de la page À propos. Formats PNG, JPG ou WebP, 2 Mo maximum." icon="image">
            <x-tk.media-input model="imageFile" :preview="$imagePreview" label="Photo ou image de présentation" remove="removeImage" hint="Choisissez une image nette et bien cadrée. L’image actuelle reste utilisée tant que vous n’enregistrez pas un remplacement." />
        </x-tk.admin-section>

        <div class="flex justify-end">
            <x-tk.button type="submit" variant="primary" icon="check">Enregistrer la photo</x-tk.button>
        </div>
    </form>
</div>
