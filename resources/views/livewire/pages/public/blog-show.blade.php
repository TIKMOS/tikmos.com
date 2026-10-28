<?php

use App\Enums\Publication;
use App\Models\Post;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    public Post $post;

    public function mount(Post $post): void
    {
        abort_unless(
            $post->status === Publication::Published
                && ($post->published_at === null || $post->published_at->isPast()),
            404
        );
        $this->post = $post->load('category', 'author');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'more' => Post::query()->published()->where('id', '!=', $this->post->id)->take(3)->get(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo :title="$post->seo_title ?: $post->title" :description="$post->seo_description ?: $post->excerpt" type="article" :image="$post->coverUrl()" />
        @php
            $articleLd = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $post->title,
                'datePublished' => $post->published_at?->toIso8601String(),
                'author' => ['@type' => 'Person', 'name' => $post->author?->name ?? config('app.name')],
                'image' => $post->coverUrl(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
        <script type="application/ld+json">{!! $articleLd !!}</script>
    @endpush

    <x-tk.section>
        <a href="{{ route('blog') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Tous les articles</a>

        <article class="mx-auto mt-6 max-w-3xl">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if ($post->category)<span class="font-mono uppercase tracking-[0.16em] text-gold-ink">{{ $post->category->name }}</span>@endif
                @if ($post->published_at)<span class="text-muted">· {{ $post->published_at->translatedFormat('d F Y') }}</span>@endif
                @if ($post->reading_time)<span class="text-muted">· {{ $post->reading_time }} min de lecture</span>@endif
            </div>
            <h1 class="mt-3 font-display text-4xl font-black leading-[1.05] tracking-[-0.02em] text-content md:text-5xl">{{ $post->title }}</h1>
            @if ($post->excerpt)<p class="mt-4 text-lg text-muted">{{ $post->excerpt }}</p>@endif

            @if ($post->coverUrl())
                <div class="mt-8 aspect-[16/9] overflow-hidden rounded-card bg-tk-100">
                    <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" class="h-full w-full object-cover">
                </div>
            @endif

            <div class="prose prose-tk mt-8 max-w-none text-content/80">{!! $post->body !!}</div>
        </article>

        @if ($more->isNotEmpty())
            <div class="mx-auto mt-16 max-w-5xl">
                <h2 class="font-display text-2xl font-extrabold text-content">À lire aussi</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $p)
                        <x-tk.post-card :post="$p" wire:key="more-{{ $p->id }}" />
                    @endforeach
                </div>
            </div>
        @endif
    </x-tk.section>
</div>
