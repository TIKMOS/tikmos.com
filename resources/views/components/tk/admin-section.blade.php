@props([
    'title' => null,
    'description' => null,
    'icon' => null,
])

{{-- Carte de réglage : en-tête (titre + aide) + contenu. --}}
<section {{ $attributes->merge(['class' => 'rounded-card border border-line bg-surface']) }}>
    @if ($title)
        <header class="flex items-start gap-3 border-b border-line px-5 py-4 sm:px-6">
            @if ($icon)
                <span class="mt-0.5 grid h-9 w-9 shrink-0 place-items-center rounded-tile bg-tk-100 text-content">
                    <x-dynamic-component :component="'lucide-'.$icon" class="h-[18px] w-[18px]" />
                </span>
            @endif
            <div class="min-w-0">
                <h2 class="font-display text-lg font-bold text-content">{{ $title }}</h2>
                @if ($description)<p class="mt-0.5 text-sm text-muted">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="ml-auto shrink-0">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="px-5 py-5 sm:px-6">
        {{ $slot }}
    </div>
</section>
