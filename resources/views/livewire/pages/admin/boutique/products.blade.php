<?php

use App\Media\ImageService;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Settings\MediaSettings;
use App\Settings\ShopSettings;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $brand = '';

    public string $sizes = ''; // saisie séparée par des virgules (ex. "40, 41, 42")

    public ?int $category_id = null;

    public ?int $price = null;

    public ?int $compare_at_price = null;

    public ?int $stock = null;

    public string $description = '';

    public bool $is_active = true;

    public bool $is_featured = false;

    public array $images = [];

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'brand', 'sizes', 'category_id', 'price', 'compare_at_price', 'stock', 'description', 'images']);
        $this->is_active = true;
        $this->is_featured = false;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $product = Product::findOrFail($id);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->brand = (string) $product->brand;
        $this->sizes = implode(', ', $product->sizeList());
        $this->category_id = $product->category_id;
        $this->price = $product->price;
        $this->compare_at_price = $product->compare_at_price;
        $this->stock = $product->stock;
        $this->description = (string) $product->description;
        $this->is_active = $product->is_active;
        $this->is_featured = $product->is_featured;
        $this->images = [];
        $this->showForm = true;
    }

    public function removeImage(int $mediaId): void
    {
        Product::findOrFail($this->editingId)->getMedia('gallery')->firstWhere('id', $mediaId)?->delete();
    }

    /** @param array<int, int|string> $ids */
    public function reorderImages(array $ids, ImageService $images): void
    {
        $images->reorder($ids);
    }

    public function updateAlt(int $mediaId, string $value, ImageService $images): void
    {
        $media = Product::findOrFail($this->editingId)->getMedia('gallery')->firstWhere('id', $mediaId);
        if ($media) {
            $images->setAlt($media, $value);
        }
    }

    public function makeCover(int $mediaId, ImageService $images): void
    {
        $collection = Product::findOrFail($this->editingId)->getMedia('gallery');
        $ordered = $collection->pluck('id')->reject(fn ($id) => $id === $mediaId)->prepend($mediaId)->all();
        $images->reorder($ordered);
    }

    public function save(ImageService $images): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:80'],
            'sizes' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:8000'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            ...$images->rules('images'),
        ]);

        $data['description'] = $this->description ? clean($this->description) : null;

        $product = $this->editingId
            ? Product::findOrFail($this->editingId)
            : new Product(['position' => (int) Product::max('position') + 1]);

        $sizes = array_values(array_filter(array_map('trim', explode(',', $this->sizes)), fn ($s) => $s !== ''));

        $product->fill([
            'name' => $data['name'],
            'brand' => $data['brand'] ?: null,
            'sizes' => $sizes ?: null,
            'category_id' => $data['category_id'],
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'],
            'stock' => $data['stock'],
            'description' => $data['description'],
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
        ]);
        if (! $product->exists || ! $product->slug) {
            $product->slug = $this->uniqueSlug($data['name']);
        }
        $product->save();

        foreach ($this->images as $image) {
            $images->attach($product, $image, 'gallery');
        }

        $this->showForm = false;
        $this->dispatch('tk-toast', type: 'success', message: $this->editingId ? 'Produit mis à jour.' : 'Produit créé.');
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    public function toggleActive(Product $product): void
    {
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
        $this->dispatch('tk-toast', type: 'success', message: 'Produit supprimé.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'products' => Product::with('category')->orderByDesc('is_featured')->latest()->paginate(12),
            'categories' => ProductCategory::orderBy('position')->get(),
            'shopEnabled' => app(ShopSettings::class)->enabled,
            'galleryMax' => app(MediaSettings::class)->gallery_max,
        ];
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <x-tk.kicker>Boutique</x-tk.kicker>
            <h1 class="mt-2 font-display text-3xl font-extrabold text-content">Produits</h1>
            @unless ($shopEnabled)<p class="mt-1 text-sm text-warning-ink">La boutique est désactivée (Réglages › Boutique). Les pages publiques renvoient 404.</p>@endunless
        </div>
        <div class="flex flex-wrap gap-2">
            <x-tk.button href="{{ route('admin.boutique.orders') }}" variant="secondary" icon="shopping-bag">Commandes</x-tk.button>
            <x-tk.button href="{{ route('admin.boutique.categories') }}" variant="secondary" icon="tags">Catégories</x-tk.button>
            <x-tk.button wire:click="create" variant="primary" icon="plus">Nouveau produit</x-tk.button>
        </div>
    </div>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($products as $product)
            <div wire:key="p-{{ $product->id }}" class="flex flex-col overflow-hidden rounded-card border border-line bg-surface">
                <div class="relative aspect-square bg-canvas">
                    @if ($product->mainImageUrl('thumb'))
                        <img src="{{ $product->mainImageUrl('thumb') }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full place-items-center"><x-lucide-image class="h-8 w-8 text-tk-300" /></div>
                    @endif
                    <div class="absolute left-2 top-2 flex gap-1">
                        @if ($product->is_featured)<x-tk.badge tone="info">En avant</x-tk.badge>@endif
                        @unless ($product->is_active)<x-tk.badge tone="neutral">Inactif</x-tk.badge>@endunless
                        @if ($product->isOutOfStock())<x-tk.badge tone="danger">Rupture</x-tk.badge>@endif
                    </div>
                </div>
                <div class="flex flex-1 flex-col p-4">
                    <p class="font-medium text-content">{{ $product->name }}</p>
                    <p class="text-xs text-muted">{{ $product->category?->name ?? 'Sans catégorie' }}</p>
                    <p class="mt-1 font-display font-bold text-content">{{ $product->priceLabel() }}</p>
                    <div class="mt-3 flex gap-2 border-t border-line pt-3">
                        <x-tk.button wire:click="edit({{ $product->id }})" variant="secondary" size="sm" icon="pencil">Éditer</x-tk.button>
                        <x-tk.button wire:click="toggleActive({{ $product->id }})" variant="ghost" size="sm">{{ $product->is_active ? 'Masquer' : 'Activer' }}</x-tk.button>
                        <button wire:click="delete({{ $product->id }})" wire:confirm="Supprimer ?" class="ml-auto grid h-9 w-9 place-items-center rounded-tile text-tk-400 hover:bg-tk-100 hover:text-danger"><x-lucide-trash-2 class="h-4 w-4" /></button>
                    </div>
                </div>
            </div>
        @empty
            <div class="sm:col-span-2 lg:col-span-3"><x-tk.empty-state icon="shopping-bag" title="Aucun produit">Ajoutez votre premier produit.</x-tk.empty-state></div>
        @endforelse
    </div>

    @if ($products->hasPages())<div class="mt-6">{{ $products->links() }}</div>@endif

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4" @keydown.escape.window="$wire.set('showForm', false)">
            <div @click.outside="$wire.set('showForm', false)" class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-t-card bg-surface p-6 shadow-lift sm:rounded-card">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-content">{{ $editingId ? 'Modifier le produit' : 'Nouveau produit' }}</h2>
                    <button @click="$wire.set('showForm', false)" class="text-tk-400 hover:text-content"><x-lucide-x class="h-5 w-5" /></button>
                </div>
                <form wire:submit="save" class="mt-5 space-y-4">
                    <x-tk.field label="Nom" :error="$errors->first('name')"><x-tk.input wire:model="name" /></x-tk.field>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Marque (optionnel)" :error="$errors->first('brand')"><x-tk.input wire:model="brand" placeholder="Maison Tikmos" /></x-tk.field>
                        <x-tk.field label="Tailles" hint="Séparées par des virgules (ex. 40, 41, 42)" :error="$errors->first('sizes')"><x-tk.input wire:model="sizes" placeholder="40, 41, 42" /></x-tk.field>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Catégorie">
                            <select wire:model="category_id" class="min-h-[48px] w-full rounded-input border border-tk-300 bg-surface px-4 text-[15px] text-content focus:border-ink focus:outline-none">
                                <option value="">— Aucune —</option>
                                @foreach ($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach
                            </select>
                        </x-tk.field>
                        <x-tk.field label="Stock" hint="Vide = illimité" :error="$errors->first('stock')"><x-tk.input wire:model="stock" type="number" min="0" /></x-tk.field>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-tk.field label="Prix (FCFA)" :error="$errors->first('price')"><x-tk.input wire:model="price" type="number" min="0" /></x-tk.field>
                        <x-tk.field label="Prix barré (optionnel)" :error="$errors->first('compare_at_price')"><x-tk.input wire:model="compare_at_price" type="number" min="0" /></x-tk.field>
                    </div>
                    <x-tk.field label="Description" :error="$errors->first('description')"><x-tk.editor wire:model="description" /></x-tk.field>

                    {{-- Galerie (upload multiple + réordonnancement + alt + couverture) --}}
                    <x-tk.media-gallery
                        :media="$editingId ? (\App\Models\Product::find($editingId)?->getMedia('gallery') ?? collect()) : collect()"
                        field="images" :max="$galleryMax" />

                    <div class="flex flex-wrap gap-6">
                        <x-tk.toggle label="Actif" wire:model="is_active" />
                        <x-tk.toggle label="Mis en avant" wire:model="is_featured" />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <x-tk.button type="submit" variant="primary"><span wire:loading.remove wire:target="save">Enregistrer</span><span wire:loading wire:target="save">…</span></x-tk.button>
                        <x-tk.button type="button" variant="ghost" x-on:click="$wire.set('showForm', false)">Annuler</x-tk.button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
