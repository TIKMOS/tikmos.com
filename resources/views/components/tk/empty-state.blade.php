@props([
    'icon' => 'package-open',
    'title' => 'Rien à afficher',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-card border border-dashed border-tk-300 bg-surface px-6 py-14 text-center']) }}>
    <span class="grid h-14 w-14 place-items-center rounded-tile bg-tk-100 text-tk-500">
        <x-dynamic-component :component="'lucide-'.$icon" class="h-7 w-7" />
    </span>
    <p class="mt-4 font-display text-lg font-bold text-content">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <div class="mt-1.5 max-w-sm text-sm text-muted">{{ $slot }}</div>
    @endif
    @isset($action)
        <div class="mt-5">{{ $action }}</div>
    @endisset
</div>
