<?php

use App\Enums\Publication;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        abort_unless($project->status === Publication::Published, 404);
        $this->project = $project;
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        return [
            'gallery' => $this->project->galleryUrls(),
            'more' => Project::query()->published()->where('id', '!=', $this->project->id)->take(3)->get(),
        ];
    }
}; ?>

<div>
    @push('meta')
        <x-tk.seo :title="$project->seo_title ?: $project->title" :description="$project->seo_description ?: $project->excerpt" />
    @endpush

    <x-tk.section>
        <a href="{{ route('projects') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Toutes les réalisations</a>

        <div class="mt-6 max-w-3xl reveal" x-data x-intersect.once="$el.classList.add('is-revealed')">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if ($project->category)<span class="font-mono uppercase tracking-[0.16em] text-gold-ink">{{ $project->category }}</span>@endif
                @if ($project->year)<span class="text-muted">· {{ $project->year }}</span>@endif
                @if ($project->client_name)<span class="text-muted">· {{ $project->client_name }}</span>@endif
            </div>
            <h1 class="mt-2 font-display text-4xl font-black leading-[1.05] tracking-[-0.02em] text-content md:text-5xl">{{ $project->title }}</h1>
            @if ($project->excerpt)<p class="mt-4 text-lg text-muted">{{ $project->excerpt }}</p>@endif
        </div>

        @if ($project->coverUrl())
            <div class="mt-8 aspect-[16/9] overflow-hidden rounded-card bg-tk-100">
                <img src="{{ $project->coverUrl() }}" alt="{{ $project->title }}" class="h-full w-full object-cover">
            </div>
        @endif

        <div class="mt-8 grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="prose prose-tk max-w-none text-content/80">{!! $project->body !!}</div>
            </div>
            @if ($project->results)
                <aside>
                    <div class="rounded-card border border-line bg-gold-100 p-6">
                        <x-tk.kicker>Résultats</x-tk.kicker>
                        <p class="mt-3 text-content">{{ $project->results }}</p>
                    </div>
                </aside>
            @endif
        </div>

        @if (count($gallery) > 0)
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($gallery as $url)
                    <div class="aspect-[4/3] overflow-hidden rounded-tile bg-tk-100"><img src="{{ $url }}" alt="{{ $project->title }}" loading="lazy" class="h-full w-full object-cover"></div>
                @endforeach
            </div>
        @endif

        @if ($more->isNotEmpty())
            <div class="mt-16">
                <h2 class="font-display text-2xl font-extrabold text-content">Autres réalisations</h2>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($more as $p)
                        <x-tk.project-card :project="$p" wire:key="more-{{ $p->id }}" />
                    @endforeach
                </div>
            </div>
        @endif
    </x-tk.section>
</div>
