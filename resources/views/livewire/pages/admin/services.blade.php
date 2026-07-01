<?php

use App\Enums\Publication;
use App\Enums\ServiceCategory;
use App\Models\Service;
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

    public string $category = 'formation';

    public string $excerpt = '';

    public string $body = '';

    public string $price_label = '';

    public string $status = 'draft';

    public $image = null;

    public ?string $currentImage = null;

    public function create(): void
    {
        $this->reset(['editingId', 'title', 'excerpt', 'body', 'price_label', 'image', 'currentImage']);
        $this->category = 'formation';
        $this->status = 'draft';
        $this->showForm = true;
    }

    public function edit(Service $service): void
    {
        $this->editingId = $service->id;
        $this->title = $service->title;
        $this->category = $service->category->value;
        $this->excerpt = (string) $service->excerpt;
        $this->body = (string) $service->body;
        $this->price_label = (string) $service->price_label;
        $this->status = $service->status->value;
        $this->currentImage = $service->imageUrl();
        $this->image = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, ServiceCategory::cases()))],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'price_label' => ['nullable', 'string', 'max:60'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $service = $this->editingId
            ? Service::findOrFail($this->editingId)
            : new Service(['position' => (int) Service::max('position') + 1]);

        $service->fill([
            'title' => $data['title'],
            'category' => $data['category'],
            'excerpt' => $data['excerpt'] ?: null,
            'body' => $data['body'] ? clean($data['body']) : null,
            'price_label' => $data['price_label'] ?: null,
            'status' => $data['status'],
        ]);

        if (! $service->slug) {
            $service->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }
        $service->save();

        if ($this->image) {
            $service->addMedia($this->image->getRealPath())
                ->usingFileName(Str::slug($this->title).'.'.$this->image->getClientOriginalExtension())
                ->toMediaCollection('illustration');
        }

        $this->showForm = false;
        $this->dispatch('tk-toast', type: 'success', message: $this->editingId ? 'Service mis à jour.' : 'Service créé.');
    }

    public function delete(Service $service): void
    {
        $service->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Service supprimé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'services' => Service::orderBy('position')->get(),
            'categories' => ServiceCategory::cases(),
            'statuses' => Publication::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">Services</h1>
            <p class="mt-1 text-muted">Catalogue affiché sur la vitrine. Créez et gérez vos offres.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Nouveau service</x-tk.button>
    </div>

    <div class="mt-8">
        @if ($services->isEmpty())
            <x-tk.empty-state icon="layers" title="Aucun service">Créez votre premier service.</x-tk.empty-state>
        @else
            <div class="space-y-2">
                @foreach ($services as $service)
                    <div wire:key="svc-{{ $service->id }}" class="flex items-center gap-4 rounded-card border border-line bg-surface p-3 pr-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-tile bg-tk-100 text-content"><x-dynamic-component :component="'lucide-'.$service->iconName()" class="h-5 w-5" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-content">{{ $service->title }}</p>
                            <p class="truncate text-xs text-muted">{{ $service->category->label() }}</p>
                        </div>
                        <x-tk.badge :tone="$service->status->tone()">{{ $service->status->label() }}</x-tk.badge>
                        <button wire:click="edit({{ $service->id }})" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $service->id }})" wire:confirm="Supprimer ce service ?" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier le service' : 'Nouveau service' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <x-tk.field label="Titre" :error="$errors->first('title')"><x-tk.input wire:model="title" placeholder="Formation Data Science" /></x-tk.field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Catégorie" :error="$errors->first('category')">
                            <select wire:model="category" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
                                @foreach ($categories as $cat)<option value="{{ $cat->value }}">{{ $cat->label() }}</option>@endforeach
                            </select>
                        </x-tk.field>
                        <x-tk.field label="Statut" :error="$errors->first('status')">
                            <select wire:model="status" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10">
                                @foreach ($statuses as $st)<option value="{{ $st->value }}">{{ $st->label() }}</option>@endforeach
                            </select>
                        </x-tk.field>
                    </div>
                    <x-tk.field label="Accroche" :error="$errors->first('excerpt')"><x-tk.input wire:model="excerpt" placeholder="Une phrase qui résume le service" /></x-tk.field>
                    <x-tk.field label="Prix affiché (optionnel)"><x-tk.input wire:model="price_label" placeholder="Sur devis" /></x-tk.field>
                    <x-tk.media-input model="image" :preview="$currentImage" label="Illustration" hint="PNG/JPG/WebP, 2 Mo." />
                    <x-tk.field label="Description" :error="$errors->first('body')">
                        <textarea wire:model="body" rows="5" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
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
