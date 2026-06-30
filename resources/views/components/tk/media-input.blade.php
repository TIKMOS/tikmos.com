@props([
    'model',                 // nom de la propriété Livewire (wire:model) recevant le fichier
    'preview' => null,       // URL de l'image existante (le cas échéant)
    'alt' => null,           // nom de la propriété Livewire pour le texte alternatif (optionnel)
    'label' => 'Image',
    'hint' => 'PNG, JPG ou WebP — 2 Mo max.',
    'remove' => null,        // nom d'une action Livewire de suppression (optionnel)
])

<div x-data="{ localPreview: null, name: null }">
    <p class="mb-1.5 block text-[13px] font-medium text-content">{{ $label }}</p>

    <div class="flex items-start gap-4">
        {{-- Aperçu --}}
        <div class="relative grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-tile border border-line bg-canvas">
            <template x-if="localPreview"><img :src="localPreview" alt="" class="h-full w-full object-cover"></template>
            @if ($preview)
                <img x-show="!localPreview" src="{{ $preview }}" alt="" class="h-full w-full object-cover">
            @endif
            <x-lucide-image x-show="!localPreview && !{{ $preview ? 'true' : 'false' }}" class="h-7 w-7 text-tk-300" />
            <div wire:loading wire:target="{{ $model }}" class="absolute inset-0 grid place-items-center bg-white/70">
                <x-lucide-loader-circle class="h-5 w-5 animate-spin text-content" />
            </div>
        </div>

        {{-- Contrôle --}}
        <div class="min-w-0 flex-1">
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-btn border border-tk-300 bg-paper px-4 py-2 text-sm font-medium text-content transition-colors hover:border-ink">
                <x-lucide-upload class="h-4 w-4" />
                <span x-text="name || 'Choisir un fichier'">Choisir un fichier</span>
                <input type="file" wire:model="{{ $model }}" accept="image/png,image/jpeg,image/webp"
                       class="sr-only"
                       @change="name = $event.target.files[0]?.name; const f = $event.target.files[0]; if (f) { const r = new FileReader(); r.onload = e => localPreview = e.target.result; r.readAsDataURL(f); }">
            </label>
            <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
            @error($model)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror

            @if ($remove && $preview)
                <button type="button" wire:click="{{ $remove }}" class="mt-2 text-xs font-medium text-danger hover:underline">Retirer l'image</button>
            @endif
        </div>
    </div>

    {{-- Texte alternatif (a11y/SEO) --}}
    @if ($alt !== null)
        <div class="mt-3">
            <label class="mb-1 block text-[13px] font-medium text-content">Texte alternatif <span class="text-muted">(décrit l'image)</span></label>
            <x-tk.input wire:model="{{ $alt }}" placeholder="Ex. Logo du partenaire DDL" />
            @error($alt)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
        </div>
    @endif
</div>
