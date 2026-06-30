@props([
    'width' => 'w-12', // largeur du filet (utilitaire Tailwind)
])

{{-- Filet or — signature premium (sous kicker, séparateur de section). --}}
<span {{ $attributes->merge(['class' => 'block h-px '.$width.' filet-gold']) }} aria-hidden="true"></span>
