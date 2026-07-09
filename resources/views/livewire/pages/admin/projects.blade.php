<?php

use App\Enums\Publication;
use App\Models\Project;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public string $client_name = '';

    public ?int $year = null;

    public string $excerpt = '';

    public string $results = '';

    public string $body = '';

    public string $status = 'draft';

    public $cover = null;

    public ?string $currentCover = null;

    public function create(): void
    {
        $this->reset(['editingId', 'title', 'category', 'client_name', 'year', 'excerpt', 'results', 'body', 'cover', 'currentCover']);
        $this->status = 'draft';
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->editingId = $project->id;
        $this->title = $project->title;
        $this->category = (string) $project->category;
        $this->client_name = (string) $project->client_name;
        $this->year = $project->year;
        $this->excerpt = (string) $project->excerpt;
        $this->results = (string) $project->results;
        $this->body = (string) $project->body;
        $this->status = $project->status->value;
        $this->currentCover = $project->coverUrl();
        $this->cover = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:60'],
            'client_name' => ['nullable', 'string', 'max:120'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'results' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'cover' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $project = $this->editingId
            ? Project::findOrFail($this->editingId)
            : new Project(['position' => (int) Project::max('position') + 1]);

        $project->fill([
            'title' => $data['title'],
            'category' => $data['category'] ?: null,
            'client_name' => $data['client_name'] ?: null,
            'year' => $data['year'],
            'excerpt' => $data['excerpt'] ?: null,
            'results' => $data['results'] ?: null,
            'body' => $data['body'] ? clean($data['body']) : null,
            'status' => $data['status'],
        ]);

        if (! $project->slug) {
            $project->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }
        $project->save();

        if ($this->cover) {
            $project->addMedia($this->cover->getRealPath())
                ->usingFileName(Str::slug($this->title).'.'.$this->cover->getClientOriginalExtension())
                ->toMediaCollection('gallery');
        }

        $this->showForm = false;
        $this->dispatch('tk-toast', type: 'success', message: $this->editingId ? 'Réalisation mise à jour.' : 'Réalisation créée.');
    }

    public function delete(int $id): void
    {
        Project::findOrFail($id)->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Réalisation supprimée.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'projects' => Project::orderBy('position')->get(),
            'statuses' => Publication::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">Réalisations</h1>
            <p class="mt-1 text-muted">Portfolio affiché sur la vitrine.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Nouvelle réalisation</x-tk.button>
    </div>

    <div class="mt-8">
        @if ($projects->isEmpty())
            <x-tk.empty-state icon="folder-open" title="Aucune réalisation">Ajoutez votre première étude de cas.</x-tk.empty-state>
        @else
            <div class="space-y-2">
                @foreach ($projects as $project)
                    <div wire:key="prj-{{ $project->id }}" class="flex items-center gap-4 rounded-card border border-line bg-surface p-3 pr-4">
                        <div class="h-12 w-16 shrink-0 overflow-hidden rounded-tile bg-tk-100">
                            @if ($project->coverUrl())<img src="{{ $project->coverUrl() }}" alt="" class="h-full w-full object-cover">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-content">{{ $project->title }}</p>
                            <p class="truncate text-xs text-muted">{{ $project->category }} @if ($project->year)· {{ $project->year }}@endif</p>
                        </div>
                        <x-tk.badge :tone="$project->status->tone()">{{ $project->status->label() }}</x-tk.badge>
                        <button wire:click="edit({{ $project->id }})" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $project->id }})" wire:confirm="Supprimer cette réalisation ?" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier' : 'Nouvelle réalisation' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <x-tk.field label="Titre" :error="$errors->first('title')"><x-tk.input wire:model="title" placeholder="Plateforme data décisionnelle" /></x-tk.field>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-tk.field label="Catégorie"><x-tk.input wire:model="category" placeholder="Data / IA" /></x-tk.field>
                        <x-tk.field label="Année" :error="$errors->first('year')"><x-tk.input wire:model="year" type="number" placeholder="2026" /></x-tk.field>
                        <x-tk.field label="Statut">
                            <select wire:model="status" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
                                @foreach ($statuses as $st)<option value="{{ $st->value }}">{{ $st->label() }}</option>@endforeach
                            </select>
                        </x-tk.field>
                    </div>
                    <x-tk.field label="Client (optionnel)"><x-tk.input wire:model="client_name" placeholder="Nom du client" /></x-tk.field>
                    <x-tk.field label="Accroche" :error="$errors->first('excerpt')"><x-tk.input wire:model="excerpt" placeholder="Résumé en une phrase" /></x-tk.field>
                    <x-tk.field label="Résultats" :error="$errors->first('results')"><x-tk.input wire:model="results" placeholder="Ex. reporting automatisé, gain de temps" /></x-tk.field>
                    <x-tk.media-input model="cover" :preview="$currentCover" label="Image de couverture" hint="PNG/JPG/WebP, 4 Mo." />
                    <x-tk.field label="Étude de cas" :error="$errors->first('body')">
                        <x-tk.editor wire:model="body" />
                    </x-tk.field>
                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary"><span wire:loading.remove wire:target="save">Enregistrer</span><span wire:loading wire:target="save">…</span></x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
