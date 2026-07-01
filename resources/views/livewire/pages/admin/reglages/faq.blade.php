<?php

use App\Models\FaqItem;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $question = '';

    public string $category = 'Général';

    public string $answer = '';

    public bool $is_published = true;

    public function create(): void
    {
        $this->reset(['editingId', 'question', 'answer']);
        $this->category = 'Général';
        $this->is_published = true;
        $this->showForm = true;
    }

    public function edit(FaqItem $item): void
    {
        $this->editingId = $item->id;
        $this->question = $item->question;
        $this->category = (string) ($item->category ?: 'Général');
        $this->answer = $item->answer;
        $this->is_published = $item->is_published;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'question' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:60'],
            'answer' => ['required', 'string', 'max:5000'],
            'is_published' => ['boolean'],
        ]);

        // Sanitisation du HTML riche (TipTap → stockage).
        $data['answer'] = clean($data['answer']);

        if ($this->editingId) {
            FaqItem::findOrFail($this->editingId)->update($data);
        } else {
            $data['position'] = (int) FaqItem::max('position') + 1;
            FaqItem::create($data);
        }

        $this->showForm = false;
        $this->dispatch('tk-toast', type: 'success', message: $this->editingId ? 'Question mise à jour.' : 'Question ajoutée.');
    }

    public function togglePublish(FaqItem $item): void
    {
        $item->update(['is_published' => ! $item->is_published]);
    }

    public function delete(FaqItem $item): void
    {
        $item->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Question supprimée.');
    }

    /** @param  array<int, string>  $ids */
    public function reorder(array $ids): void
    {
        foreach ($ids as $position => $id) {
            FaqItem::whereKey($id)->update(['position' => $position]);
        }
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return ['items' => FaqItem::orderBy('position')->get()];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.reglages') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Réglages</a>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">FAQ</h1>
            <p class="mt-1 text-muted">Questions fréquentes affichées sur l'accueil. Glissez pour réordonner.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Nouvelle question</x-tk.button>
    </div>

    <div class="mt-8">
        @if ($items->isEmpty())
            <x-tk.empty-state icon="circle-help" title="Aucune question">Ajoutez votre première question fréquente.</x-tk.empty-state>
        @else
            <x-tk.sortable-list onReorder="reorder">
                @foreach ($items as $item)
                    <li wire:key="faq-{{ $item->id }}" data-id="{{ $item->id }}" class="flex items-center gap-3 rounded-card border border-line bg-surface p-3 pr-4">
                        <x-lucide-grip-vertical class="h-5 w-5 shrink-0 cursor-grab text-tk-300" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-content">{{ $item->question }}</p>
                            <p class="text-xs text-muted">{{ $item->category }}</p>
                        </div>
                        <button wire:click="togglePublish({{ $item->id }})" class="shrink-0">
                            <x-tk.badge :tone="$item->is_published ? 'success' : 'neutral'" dot>{{ $item->is_published ? 'Publiée' : 'Brouillon' }}</x-tk.badge>
                        </button>
                        <button wire:click="edit({{ $item->id }})" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $item->id }})" wire:confirm="Supprimer cette question ?" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </li>
                @endforeach
            </x-tk.sortable-list>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier la question' : 'Nouvelle question' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-[1fr_auto]">
                        <x-tk.field label="Question" :error="$errors->first('question')"><x-tk.input wire:model="question" placeholder="Combien de temps pour…" /></x-tk.field>
                        <x-tk.field label="Catégorie" :error="$errors->first('category')"><x-tk.input wire:model="category" class="sm:w-40" /></x-tk.field>
                    </div>
                    <x-tk.field label="Réponse" :error="$errors->first('answer')">
                        <x-tk.editor wire:model="answer" />
                    </x-tk.field>
                    <x-tk.toggle label="Publiée" description="Visible sur le site." wire:model="is_published" />
                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary">
                            <span wire:loading.remove wire:target="save">Enregistrer</span>
                            <span wire:loading wire:target="save">…</span>
                        </x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
