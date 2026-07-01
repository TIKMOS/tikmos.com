<?php

use App\Settings\MediaSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    /** @var array<int, string> */
    public array $accepted_formats = [];

    public int $max_size_kb = 4096;

    public int $quality = 82;

    public bool $blur_up = true;

    public int $gallery_max = 8;

    public function mount(MediaSettings $s): void
    {
        $this->accepted_formats = $s->accepted_formats;
        $this->max_size_kb = $s->max_size_kb;
        $this->quality = $s->quality;
        $this->blur_up = $s->blur_up;
        $this->gallery_max = $s->gallery_max;
    }

    public function save(MediaSettings $s): void
    {
        $this->validate([
            'accepted_formats' => ['array', 'min:1'],
            'accepted_formats.*' => ['string', 'in:png,jpg,jpeg,webp,avif,gif'],
            'max_size_kb' => ['required', 'integer', 'min:256', 'max:20480'],
            'quality' => ['required', 'integer', 'min:40', 'max:100'],
            'blur_up' => ['boolean'],
            'gallery_max' => ['required', 'integer', 'min:1', 'max:30'],
        ]);

        $s->accepted_formats = array_values($this->accepted_formats);
        $s->max_size_kb = $this->max_size_kb;
        $s->quality = $this->quality;
        $s->blur_up = $this->blur_up;
        $s->gallery_max = $this->gallery_max;
        $s->save();

        $this->dispatch('tk-toast', type: 'success', message: 'Réglages médias enregistrés.');
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Médias</h1>
        </div>
        <x-tk.button wire:click="save" variant="primary" icon="check">
            <span wire:loading.remove wire:target="save">Enregistrer</span>
            <span wire:loading wire:target="save">Enregistrement…</span>
        </x-tk.button>
    </div>

    <form wire:submit="save" class="mt-8 space-y-5">
        <x-tk.admin-section title="Formats & poids" description="Types d'images acceptés et taille maximale par fichier." icon="file-image">
            <div class="space-y-4">
                <div>
                    <p class="mb-1.5 text-[13px] font-medium text-content">Formats acceptés</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach (['png', 'jpg', 'jpeg', 'webp', 'avif', 'gif'] as $fmt)
                            <label class="inline-flex items-center gap-2 rounded-tile border border-line px-3 py-2 text-sm">
                                <input type="checkbox" wire:model="accepted_formats" value="{{ $fmt }}" class="h-4 w-4 rounded border-tk-300 text-ink focus:ring-ink/20">
                                {{ strtoupper($fmt) }}
                            </label>
                        @endforeach
                    </div>
                    @error('accepted_formats')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <x-tk.field label="Poids max (Ko)" :error="$errors->first('max_size_kb')"><x-tk.input wire:model="max_size_kb" type="number" min="256" /></x-tk.field>
                    <x-tk.field label="Qualité WebP (%)" hint="Compression des conversions." :error="$errors->first('quality')"><x-tk.input wire:model="quality" type="number" min="40" max="100" /></x-tk.field>
                    <x-tk.field label="Images max / galerie" :error="$errors->first('gallery_max')"><x-tk.input wire:model="gallery_max" type="number" min="1" max="30" /></x-tk.field>
                </div>
            </div>
        </x-tk.admin-section>

        <x-tk.admin-section title="Rendu" description="Optimisations d'affichage sur la vitrine." icon="sparkles">
            <x-tk.toggle label="Placeholder flou pendant le chargement (blur-up)" wire:model="blur_up" />
            <p class="mt-2 text-sm text-muted">Les images sont servies en WebP avec <code>srcset</code> responsive, chargement différé et réservation d'espace (zéro décalage de mise en page).</p>
        </x-tk.admin-section>

        <div class="flex justify-end">
            <x-tk.button type="submit" variant="primary" icon="check">Enregistrer les réglages</x-tk.button>
        </div>
    </form>
</div>
