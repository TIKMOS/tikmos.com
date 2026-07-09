@props([
    'label' => null,
    'uploadUrl' => null,
])

@php
    $model = $attributes->wire('model')->value();
    // URL d'upload d'images inline (contrat POST file → { url }). Par défaut : route admin.
    $uploadUrl ??= \Illuminate\Support\Facades\Route::has('admin.editor.image') ? route('admin.editor.image') : null;
    $btn = 'grid h-8 w-8 place-items-center rounded-tile text-content transition-colors hover:bg-tk-100 disabled:opacity-40 disabled:pointer-events-none';
    $tbtn = 'rounded-tile px-2 py-1 text-xs font-medium text-content transition-colors hover:bg-tk-100';
@endphp

<div>
    @if ($label)<label class="mb-1.5 block text-[13px] font-medium text-content">{{ $label }}</label>@endif

    <div x-data="szEditor(@js($model), @js($uploadUrl))" x-on:livewire:navigating.window="destroy()"
         {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'rounded-input border border-tk-300 bg-surface focus-within:border-ink focus-within:ring-4 focus-within:ring-ink/10 transition-colors']) }}>

        {{-- Barre d'outils --}}
        <div class="flex flex-wrap items-center gap-0.5 border-b border-line px-2 py-1.5" wire:ignore>
            <button type="button" class="{{ $btn }}" :class="active.bold && 'bg-ink text-paper hover:bg-ink'" @click="run('bold')" title="Gras" aria-label="Gras"><x-lucide-bold class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.italic && 'bg-ink text-paper hover:bg-ink'" @click="run('italic')" title="Italique" aria-label="Italique"><x-lucide-italic class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.underline && 'bg-ink text-paper hover:bg-ink'" @click="run('underline')" title="Souligné" aria-label="Souligné"><x-lucide-underline class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.strike && 'bg-ink text-paper hover:bg-ink'" @click="run('strike')" title="Barré" aria-label="Barré"><x-lucide-strikethrough class="h-4 w-4" /></button>
            <span class="mx-1 h-5 w-px bg-line"></span>
            <button type="button" class="{{ $btn }}" :class="active.h2 && 'bg-ink text-paper hover:bg-ink'" @click="run('h2')" title="Titre" aria-label="Titre niveau 2"><x-lucide-heading-2 class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.h3 && 'bg-ink text-paper hover:bg-ink'" @click="run('h3')" title="Sous-titre" aria-label="Titre niveau 3"><x-lucide-heading-3 class="h-4 w-4" /></button>
            <span class="mx-1 h-5 w-px bg-line"></span>
            <button type="button" class="{{ $btn }}" :class="active.alignLeft && 'bg-ink text-paper hover:bg-ink'" @click="run('alignLeft')" title="Aligner à gauche" aria-label="Aligner à gauche"><x-lucide-align-left class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.alignCenter && 'bg-ink text-paper hover:bg-ink'" @click="run('alignCenter')" title="Centrer" aria-label="Centrer"><x-lucide-align-center class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.alignRight && 'bg-ink text-paper hover:bg-ink'" @click="run('alignRight')" title="Aligner à droite" aria-label="Aligner à droite"><x-lucide-align-right class="h-4 w-4" /></button>
            <span class="mx-1 h-5 w-px bg-line"></span>
            <button type="button" class="{{ $btn }}" :class="active.bullet && 'bg-ink text-paper hover:bg-ink'" @click="run('bullet')" title="Liste à puces" aria-label="Liste à puces"><x-lucide-list class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.ordered && 'bg-ink text-paper hover:bg-ink'" @click="run('ordered')" title="Liste numérotée" aria-label="Liste numérotée"><x-lucide-list-ordered class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.blockquote && 'bg-ink text-paper hover:bg-ink'" @click="run('blockquote')" title="Citation" aria-label="Citation"><x-lucide-text-quote class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :class="active.codeBlock && 'bg-ink text-paper hover:bg-ink'" @click="run('codeBlock')" title="Bloc de code" aria-label="Bloc de code"><x-lucide-code class="h-4 w-4" /></button>
            <span class="mx-1 h-5 w-px bg-line"></span>
            <button type="button" class="{{ $btn }}" :class="active.link && 'bg-ink text-paper hover:bg-ink'" @click="toggleLink()" title="Lien" aria-label="Lien"><x-lucide-link class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" @click="pickImage()" :disabled="uploading" title="Image" aria-label="Insérer une image">
                <x-lucide-image x-show="!uploading" class="h-4 w-4" />
                <x-lucide-loader-circle x-show="uploading" x-cloak class="h-4 w-4 animate-spin" />
            </button>
            <button type="button" class="{{ $btn }}" @click="run('table')" title="Tableau" aria-label="Insérer un tableau"><x-lucide-table class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" @click="run('hr')" title="Séparateur" aria-label="Séparateur"><x-lucide-minus class="h-4 w-4" /></button>
            <span class="mx-1 h-5 w-px bg-line"></span>
            <button type="button" class="{{ $btn }}" @click="run('clear')" title="Effacer la mise en forme" aria-label="Effacer la mise en forme"><x-lucide-eraser class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :disabled="!canUndo" @click="run('undo')" title="Annuler" aria-label="Annuler"><x-lucide-undo-2 class="h-4 w-4" /></button>
            <button type="button" class="{{ $btn }}" :disabled="!canRedo" @click="run('redo')" title="Rétablir" aria-label="Rétablir"><x-lucide-redo-2 class="h-4 w-4" /></button>

            <input type="file" x-ref="imageInput" @change="onImagePicked($event)" accept="image/*" class="hidden" />
        </div>

        {{-- Barre contextuelle « tableau » (visible uniquement dans un tableau) --}}
        <div x-show="inTable" x-cloak class="flex flex-wrap items-center gap-1 border-b border-line bg-tk-50 px-2 py-1.5" wire:ignore>
            <span class="mr-1 text-[11px] font-semibold uppercase tracking-wide text-muted">Tableau</span>
            <button type="button" class="{{ $tbtn }}" @click="run('addRow')">+ Ligne</button>
            <button type="button" class="{{ $tbtn }}" @click="run('addCol')">+ Colonne</button>
            <button type="button" class="{{ $tbtn }}" @click="run('delRow')">− Ligne</button>
            <button type="button" class="{{ $tbtn }}" @click="run('delCol')">− Colonne</button>
            <button type="button" class="{{ $tbtn }} text-danger hover:bg-danger-soft" @click="run('delTable')">Supprimer le tableau</button>
        </div>

        {{-- Zone d'édition (préservée du re-render Livewire) --}}
        <div class="prose-tk max-w-none px-4 py-3" wire:ignore>
            <div x-ref="editor"></div>
        </div>
    </div>
    @error($model)<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
</div>
