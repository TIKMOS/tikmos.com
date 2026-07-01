@props([
    'variant' => 'full',   // conservé pour compat (le logo officiel est un lockup unique)
    'invert' => false,     // true => version pour fonds sombres (footer / sidebar)
])

@php
    // « Détourage » sans édition d'image :
    // - fond clair : multiply → le fond blanc du logo se fond dans l'en-tête.
    // - fond sombre : screen  → le fond noir du logo se fond dans le bleu roi.
    $blend = $invert ? 'mix-blend-screen' : 'mix-blend-multiply';
@endphp

<img
    src="{{ asset($invert ? 'img/tikmos-logo-dark.jpg' : 'img/tikmos-logo.png') }}"
    alt="{{ config('app.name', 'Tikmos') }}"
    {{ $attributes->merge(['class' => 'h-11 w-auto select-none '.$blend]) }}
/>
