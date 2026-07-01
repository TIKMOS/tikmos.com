<?php

use App\Enums\Publication;
use App\Models\Post;
use App\Models\PostCategory;
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

    public ?int $post_category_id = null;

    public string $excerpt = '';

    public string $body = '';

    public ?int $reading_time = null;

    public string $published_at = '';

    public string $status = 'draft';

    public $cover = null;

    public ?string $currentCover = null;

    public function create(): void
    {
        $this->reset(['editingId', 'title', 'post_category_id', 'excerpt', 'body', 'reading_time', 'published_at', 'cover', 'currentCover']);
        $this->status = 'draft';
        $this->published_at = now()->format('Y-m-d');
        $this->showForm = true;
    }

    public function edit(Post $post): void
    {
        $this->editingId = $post->id;
        $this->title = $post->title;
        $this->post_category_id = $post->post_category_id;
        $this->excerpt = (string) $post->excerpt;
        $this->body = (string) $post->body;
        $this->reading_time = $post->reading_time;
        $this->published_at = $post->published_at?->format('Y-m-d') ?? '';
        $this->status = $post->status->value;
        $this->currentCover = $post->coverUrl();
        $this->cover = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'post_category_id' => ['nullable', 'exists:post_categories,id'],
            'excerpt' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'reading_time' => ['nullable', 'integer', 'min:1', 'max:120'],
            'published_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'cover' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $post = $this->editingId
            ? Post::findOrFail($this->editingId)
            : new Post(['author_id' => auth()->id()]);

        $post->fill([
            'title' => $data['title'],
            'post_category_id' => $data['post_category_id'],
            'excerpt' => $data['excerpt'] ?: null,
            'body' => $data['body'] ? clean($data['body']) : null,
            'reading_time' => $data['reading_time'],
            'published_at' => $data['published_at'] ?: null,
            'status' => $data['status'],
        ]);

        if (! $post->slug) {
            $post->slug = Str::slug($data['title']).'-'.Str::lower(Str::random(4));
        }
        $post->save();

        if ($this->cover) {
            $post->addMedia($this->cover->getRealPath())
                ->usingFileName(Str::slug($this->title).'.'.$this->cover->getClientOriginalExtension())
                ->toMediaCollection('cover');
        }

        $this->showForm = false;
        $this->dispatch('sz-toast', type: 'success', message: $this->editingId ? 'Article mis à jour.' : 'Article créé.');
    }

    public function delete(Post $post): void
    {
        $post->delete();
        $this->dispatch('sz-toast', type: 'success', message: 'Article supprimé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'posts' => Post::with('category')->latest('published_at')->latest()->get(),
            'categories' => PostCategory::orderBy('position')->get(),
            'statuses' => Publication::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">Blog</h1>
            <p class="mt-1 text-muted">Articles et actualités affichés sur la vitrine.</p>
        </div>
        <x-tk.button wire:click="create" variant="primary" icon="plus">Nouvel article</x-tk.button>
    </div>

    <div class="mt-8">
        @if ($posts->isEmpty())
            <x-tk.empty-state icon="newspaper" title="Aucun article">Publiez votre premier article.</x-tk.empty-state>
        @else
            <div class="space-y-2">
                @foreach ($posts as $post)
                    <div wire:key="post-{{ $post->id }}" class="flex items-center gap-4 rounded-card border border-line bg-surface p-3 pr-4">
                        <div class="h-12 w-16 shrink-0 overflow-hidden rounded-tile bg-tk-100">
                            @if ($post->coverUrl())<img src="{{ $post->coverUrl() }}" alt="" class="h-full w-full object-cover">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-content">{{ $post->title }}</p>
                            <p class="truncate text-xs text-muted">{{ $post->category?->name }} @if ($post->published_at)· {{ $post->published_at->format('d/m/Y') }}@endif</p>
                        </div>
                        <x-tk.badge :tone="$post->status->tone()">{{ $post->status->label() }}</x-tk.badge>
                        <button wire:click="edit({{ $post->id }})" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-500 hover:bg-tk-100"><x-lucide-pencil class="h-4 w-4" /></button>
                        <button wire:click="delete({{ $post->id }})" wire:confirm="Supprimer cet article ?" class="grid h-9 w-9 shrink-0 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier' : 'Nouvel article' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <x-tk.field label="Titre" :error="$errors->first('title')"><x-tk.input wire:model="title" placeholder="Titre de l'article" /></x-tk.field>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-tk.field label="Catégorie">
                            <select wire:model="post_category_id" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none">
                                <option value="">— Aucune —</option>
                                @foreach ($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                            </select>
                        </x-tk.field>
                        <x-tk.field label="Date" :error="$errors->first('published_at')"><x-tk.input wire:model="published_at" type="date" /></x-tk.field>
                        <x-tk.field label="Statut">
                            <select wire:model="status" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-3 text-[15px] text-content focus:border-ink focus:outline-none">
                                @foreach ($statuses as $st)<option value="{{ $st->value }}">{{ $st->label() }}</option>@endforeach
                            </select>
                        </x-tk.field>
                    </div>
                    <x-tk.field label="Accroche" :error="$errors->first('excerpt')"><x-tk.input wire:model="excerpt" placeholder="Résumé en une phrase" /></x-tk.field>
                    <x-tk.field label="Temps de lecture (min)"><x-tk.input wire:model="reading_time" type="number" min="1" placeholder="5" /></x-tk.field>
                    <x-tk.media-input model="cover" :preview="$currentCover" label="Image de couverture" hint="PNG/JPG/WebP, 4 Mo." />
                    <x-tk.field label="Contenu" :error="$errors->first('body')">
                        <textarea wire:model="body" rows="6" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
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
