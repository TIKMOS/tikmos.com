@props([
    'variant' => 'full',   // conservé pour compat (le logo officiel est un lockup unique)
    'invert' => false,     // true => version pour fonds sombres (footer encre)
])

{{--
    Logo officiel Tikmos (marine + or champagne).
    NB: assets provisoires (PNG/JPEG). TODO prod : SVG + variante fond transparent / blanche.
    - clair  (header ivoire) : tikmos-logo.png (marine sur blanc)
    - sombre (footer encre)  : tikmos-logo-dark.jpg (marine + or sur noir)
--}}
<img
    src="{{ asset($invert ? 'img/tikmos-logo-dark.jpg' : 'img/tikmos-logo.png') }}"
    alt="{{ config('app.name', 'Tikmos') }}"
    {{ $attributes->merge(['class' => 'h-10 w-auto select-none']) }}
/>
