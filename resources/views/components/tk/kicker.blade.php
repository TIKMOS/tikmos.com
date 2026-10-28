@props(['tone' => 'gold']) {{-- gold (signature) | muted | accent --}}

@php
    $color = match ($tone) {
        'muted' => 'text-muted',
        'accent' => 'text-content',
        default => 'text-gold-ink', // Or de texte ; l'or décoratif reste inchangé.
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-block font-mono text-xs font-semibold uppercase tracking-[0.2em] '.$color]) }}>
    {{ $slot }}
</span>
