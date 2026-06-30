@props(['tone' => 'gold']) {{-- gold (signature) | muted | accent --}}

@php
    $color = match ($tone) {
        'muted' => 'text-tk-500',
        'accent' => 'text-content',
        default => 'text-gold-600', // signature : sur-titre or champagne
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-block font-mono text-xs font-semibold uppercase tracking-[0.2em] '.$color]) }}>
    {{ $slot }}
</span>
