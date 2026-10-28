@props([
    'post', // App\Models\Post
])

<a href="{{ route('blog.show', $post->slug) }}" wire:navigate
   {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-card border border-line/60 bg-surface transition-all hover:-translate-y-1 hover:shadow-pop']) }}>
    <div class="aspect-[16/10] overflow-hidden bg-tk-100">
        @if ($post->coverUrl())
            <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="grid h-full place-items-center"><x-lucide-newspaper class="h-10 w-10 text-tk-300" /></div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs">
            @if ($post->category)<span class="font-mono uppercase tracking-[0.14em] text-gold-ink">{{ $post->category->name }}</span>@endif
            @if ($post->published_at)<span class="text-muted">· {{ $post->published_at->translatedFormat('d M Y') }}</span>@endif
        </div>
        <h3 class="mt-1.5 font-display text-lg font-bold text-content">{{ $post->title }}</h3>
        <p class="mt-2 flex-1 text-sm text-muted">{{ $post->excerpt }}</p>
        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-content group-hover:gap-2">
            Lire l'article <x-lucide-arrow-right class="h-4 w-4 transition-all" />
            @if ($post->reading_time)<span class="ml-auto text-muted">{{ $post->reading_time }} min</span>@endif
        </span>
    </div>
</a>
