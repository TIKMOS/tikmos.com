@props([
    'name' => '',
    'size' => 'md', // sm | md | lg
])

@php
    $initials = collect(explode(' ', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');

    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-grid place-items-center rounded-tile bg-ink font-display font-bold text-paper '.($sizes[$size] ?? $sizes['md'])]) }}>
    {{ $initials ?: '•' }}
</span>
