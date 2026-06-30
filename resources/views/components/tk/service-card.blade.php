@props([
    'service', // App\Models\Service
])

<a href="{{ route('services.show', $service->slug) }}" wire:navigate
   {{ $attributes->merge(['class' => 'group flex h-full flex-col rounded-card border border-line/60 bg-surface p-6 transition-all hover:-translate-y-1 hover:shadow-pop']) }}>
    @if ($service->imageUrl())
        <div class="mb-5 aspect-[4/3] overflow-hidden rounded-tile bg-tk-100">
            <img src="{{ $service->imageUrl() }}" alt="{{ $service->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        </div>
    @else
        <span class="grid h-12 w-12 place-items-center rounded-tile bg-ink text-paper">
            <x-dynamic-component :component="'lucide-'.$service->iconName()" class="h-6 w-6" />
        </span>
    @endif

    <p class="mt-5 font-mono text-xs uppercase tracking-[0.16em] text-gold-600">{{ $service->category->label() }}</p>
    <h3 class="mt-1.5 font-display text-lg font-bold text-content">{{ $service->title }}</h3>
    <p class="mt-2 flex-1 text-sm text-muted">{{ $service->excerpt }}</p>

    <span class="mt-5 inline-flex items-center gap-1 text-sm font-medium text-content group-hover:gap-2">
        @if ($service->price_label)<span class="mr-auto text-gold-600">{{ $service->price_label }}</span>@endif
        En savoir plus <x-lucide-arrow-right class="h-4 w-4 transition-all" />
    </span>
</a>
