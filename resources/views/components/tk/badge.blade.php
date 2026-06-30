@props([
    'tone' => 'neutral', // neutral | accent | success | warning | danger | info
    'dot' => false,
])

@php
    $tones = [
        'neutral' => 'bg-tk-100 text-tk-700',
        'accent' => 'bg-tk-100 text-content',
        'success' => 'bg-success-soft text-success-ink',
        'warning' => 'bg-warning-soft text-warning-ink',
        'danger' => 'bg-danger-soft text-danger-ink',
        'info' => 'bg-info-soft text-info-ink',
    ];
    $dotColors = [
        'neutral' => 'bg-tk-500',
        'accent' => 'bg-ink',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'info' => 'bg-info',
    ];
    $classes = 'inline-flex items-center gap-1.5 rounded-pill px-3 py-1 text-xs font-semibold '.($tones[$tone] ?? $tones['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)<span class="h-1.5 w-1.5 rounded-full {{ $dotColors[$tone] ?? $dotColors['neutral'] }}"></span>@endif
    {{ $slot }}
</span>
