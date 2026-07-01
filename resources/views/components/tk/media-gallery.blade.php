@props([
    'media' => collect(),          // Illuminate collection de Media existants (ordonnés)
    'field' => 'images',           // propriété Livewire recevant les nouveaux fichiers
    'max' => 8,
    'reorder' => 'reorderImages',  // méthode Livewire(ids[])
    'remove' => 'removeImage',     // méthode Livewire(id)
    'alt' => 'updateAlt',          // méthode Livewire(id, value)
    'cover' => 'makeCover',        // méthode Livewire(id)
    'label' => 'Images',
    'hint' => null,
])

@php $hint ??= "Jusqu'à {$max} images. Glissez pour réordonner — la 1ʳᵉ est l'image de couverture."; @endphp

<div>
    <p class="mb-1.5 block text-[13px] font-medium text-content">{{ $label }}</p>

    {{-- Grille des médias existants (réordonnable) --}}
    @if ($media->isNotEmpty())
        <div
            x-data="szSortable(@js($reorder))"
            wire:key="gallery-{{ $media->pluck('id')->implode('-') }}"
            class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-3"
        >
            @foreach ($media as $index => $m)
                <div data-id="{{ $m->id }}" wire:key="media-{{ $m->id }}"
                     class="group relative cursor-move rounded-tile border border-line bg-canvas p-2">
                    <div class="relative aspect-square overflow-hidden rounded-[10px]">
                        <img src="{{ $m->getUrl('thumb') }}" alt="{{ $m->getCustomProperty('alt') }}" class="h-full w-full object-cover" />
                        @if ($index === 0)
                            <span class="absolute left-1 top-1 rounded-full bg-ink px-2 py-0.5 text-[10px] font-bold text-paper">Couverture</span>
                        @endif
                        <button type="button" wire:click="{{ $remove }}({{ $m->id }})"
                                class="absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-black/60 text-white opacity-0 transition group-hover:opacity-100">
                            <x-lucide-x class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <input type="text" value="{{ $m->getCustomProperty('alt') }}"
                           wire:change="{{ $alt }}({{ $m->id }}, $event.target.value)"
                           placeholder="Texte alternatif…"
                           class="mt-2 w-full rounded-[8px] border border-tk-200 bg-surface px-2 py-1 text-xs text-content focus:border-ink focus:outline-none" />
                    @if ($index !== 0)
                        <button type="button" wire:click="{{ $cover }}({{ $m->id }})"
                                class="mt-1 text-[11px] font-medium text-muted hover:text-content hover:underline">Définir en couverture</button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Zone d'upload --}}
    <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-tile border-2 border-dashed border-tk-300 bg-paper px-4 py-6 text-center transition hover:border-ink">
        <x-lucide-upload-cloud class="h-6 w-6 text-tk-400" />
        <span class="text-sm font-medium text-content">Choisir des images</span>
        <span class="text-xs text-muted">{{ $hint }}</span>
        <input type="file" wire:model="{{ $field }}" multiple accept="image/png,image/jpeg,image/webp" class="sr-only" />
    </label>
    <div wire:loading wire:target="{{ $field }}" class="mt-1 text-xs text-muted">Téléversement…</div>
    @error($field.'.*')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
    @error($field)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
</div>
