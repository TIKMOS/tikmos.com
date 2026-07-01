<?php

use App\Models\Post;
use App\Models\PostCategory;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.public')] class extends Component
{
    use WithPagination;

    #[Url(as: 'cat')]
    public string $category = '';

    public function setCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $query = Post::query()->published()->with('category');

        if ($this->category !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->category));
        }

        return [
            'posts' => $query->paginate(9),
            'categories' => PostCategory::published()->get(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo title="Blog" description="Articles et actualités Tikmos : data, IA, conseils et digitalisation pour votre activité." />
    @endpush

    <x-tk.section tone="canvas">
        <div class="max-w-2xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <x-tk.kicker>Blog</x-tk.kicker>
            <x-tk.divider-gold class="mt-3" />
            <h1 class="mt-3 font-display text-4xl font-black leading-[1] tracking-[-0.02em] text-content md:text-5xl">Idées, conseils & actualités.</h1>
            <p class="mt-4 text-lg text-muted">Data, IA et digitalisation — de quoi nourrir vos projets.</p>
        </div>

        @if ($categories->isNotEmpty())
            <div class="mt-8 flex flex-wrap gap-2">
                <button wire:click="setCategory('')" class="rounded-btn border px-4 py-2 text-sm font-medium transition-colors {{ $category === '' ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">Tous</button>
                @foreach ($categories as $cat)
                    <button wire:click="setCategory('{{ $cat->slug }}')" class="rounded-btn border px-4 py-2 text-sm font-medium transition-colors {{ $category === $cat->slug ? 'border-ink bg-ink text-paper' : 'border-tk-300 text-content hover:border-ink' }}">{{ $cat->name }}</button>
                @endforeach
            </div>
        @endif

        @if ($posts->isEmpty())
            <x-tk.empty-state class="mt-10" icon="newspaper" title="Bientôt">Les premiers articles arrivent.</x-tk.empty-state>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    <x-tk.post-card :post="$post" wire:key="post-{{ $post->id }}" />
                @endforeach
            </div>
            @if ($posts->hasPages())<div class="mt-8">{{ $posts->links() }}</div>@endif
        @endif
    </x-tk.section>
</div>
