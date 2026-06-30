<?php

use App\Models\LegalDocument;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $editingId = null;

    public string $title = '';

    public string $body = '';

    public bool $is_published = true;

    public function edit(LegalDocument $document): void
    {
        $this->editingId = $document->id;
        $this->title = $document->title;
        $this->body = $document->body;
        $this->is_published = $document->is_published;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'title', 'body', 'is_published']);
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:30000'],
            'is_published' => ['boolean'],
        ]);

        $doc = LegalDocument::findOrFail($this->editingId);
        $doc->update([
            'title' => $data['title'],
            'body' => clean($data['body']),
            'is_published' => $this->is_published,
            'version' => $doc->version + 1,
        ]);

        $this->cancel();
        $this->dispatch('sz-toast', type: 'success', message: 'Page enregistrée (v'.$doc->version.').');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['documents' => LegalDocument::orderBy('key')->get()];
    }
}; ?>

<div>
    <div>
        <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
        <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Pages légales</h1>
        <p class="mt-1 text-muted">CGU, confidentialité — éditeur riche. Le sommaire est généré depuis les titres (H2).</p>
    </div>

    @if ($editingId)
        {{-- Éditeur --}}
        <x-tk.admin-section class="mt-8" title="Édition" :description="'Document : '.$title">
            <x-slot:actions>
                <x-tk.button wire:click="cancel" variant="ghost" size="sm">Fermer</x-tk.button>
            </x-slot:actions>
            <form wire:submit="save" class="space-y-4">
                <x-tk.field label="Titre" :error="$errors->first('title')"><x-tk.input wire:model="title" /></x-tk.field>
                <x-tk.field label="Contenu" hint="Utilisez les titres (H2) pour structurer : ils alimentent le sommaire." :error="$errors->first('body')">
                    <x-tk.editor wire:model="body" />
                </x-tk.field>
                <x-tk.toggle label="Publiée" description="Visible sur le site." wire:model="is_published" />
                <div class="flex gap-3 pt-2">
                    <x-tk.button type="submit" variant="primary" icon="check">
                        <span wire:loading.remove wire:target="save">Enregistrer</span>
                        <span wire:loading wire:target="save">…</span>
                    </x-tk.button>
                    <x-tk.button type="button" variant="ghost" wire:click="cancel">Annuler</x-tk.button>
                </div>
            </form>
        </x-tk.admin-section>
    @else
        <div class="mt-8 grid gap-4 sm:grid-cols-2">
            @foreach ($documents as $doc)
                <div class="flex flex-col rounded-card border border-line bg-surface p-6">
                    <div class="flex items-center justify-between">
                        <span class="grid h-11 w-11 place-items-center rounded-tile bg-ink text-paper"><x-lucide-file-text class="h-5 w-5" /></span>
                        <x-tk.badge :tone="$doc->is_published ? 'success' : 'neutral'" dot>{{ $doc->is_published ? 'Publiée' : 'Brouillon' }}</x-tk.badge>
                    </div>
                    <h2 class="mt-4 font-display text-lg font-bold text-content">{{ $doc->title }}</h2>
                    <p class="mt-1 text-sm text-muted">Version {{ $doc->version }} · maj {{ $doc->updated_at->translatedFormat('d M Y') }}</p>
                    <div class="mt-5 flex gap-2">
                        <x-tk.button wire:click="edit({{ $doc->id }})" variant="primary" size="sm" icon="pencil">Éditer</x-tk.button>
                        <x-tk.button href="{{ url($doc->key === 'cgu' ? '/cgu' : '/confidentialite') }}" variant="secondary" size="sm" icon="external-link">Voir</x-tk.button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
