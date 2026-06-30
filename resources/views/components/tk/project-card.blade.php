@props([
    'project', // App\Models\Project
])

<a href="{{ route('projects.show', $project->slug) }}" wire:navigate
   {{ $attributes->merge(['class' => 'group flex h-full flex-col overflow-hidden rounded-card border border-line/60 bg-surface transition-all hover:-translate-y-1 hover:shadow-pop']) }}>
    <div class="aspect-[16/10] overflow-hidden bg-tk-100">
        @if ($project->coverUrl())
            <img src="{{ $project->coverUrl() }}" alt="{{ $project->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <div class="grid h-full place-items-center"><x-lucide-image class="h-10 w-10 text-tk-300" /></div>
        @endif
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center gap-2 text-xs">
            @if ($project->category)<span class="font-mono uppercase tracking-[0.14em] text-gold-600">{{ $project->category }}</span>@endif
            @if ($project->year)<span class="text-tk-400">· {{ $project->year }}</span>@endif
        </div>
        <h3 class="mt-1.5 font-display text-lg font-bold text-content">{{ $project->title }}</h3>
        <p class="mt-2 flex-1 text-sm text-muted">{{ $project->excerpt }}</p>
        <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-content group-hover:gap-2">Voir l'étude de cas <x-lucide-arrow-right class="h-4 w-4 transition-all" /></span>
    </div>
</a>
