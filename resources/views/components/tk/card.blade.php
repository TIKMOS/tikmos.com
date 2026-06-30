@props([
    'variant' => 'default', // default | dark | flat
    'padding' => 'p-6',
])

@php
    $variants = [
        'default' => 'bg-surface border border-line shadow-card',
        'dark' => 'bg-ink text-paper border border-ink',
        'flat' => 'bg-surface border border-line',
    ];
    $classes = 'rounded-card '.$padding.' '.($variants[$variant] ?? $variants['default']);
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
