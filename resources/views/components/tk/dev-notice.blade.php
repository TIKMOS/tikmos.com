@props(['label' => 'Mode développement'])

{{-- Bandeau visible uniquement en local (le caller garde l'affichage). Jamais en production. --}}
<div {{ $attributes->merge(['class' => 'rounded-card border border-dashed border-warning/60 bg-warning-soft px-4 py-3']) }} role="note">
    <div class="flex items-center gap-2 text-warning-ink">
        <x-lucide-code class="h-4 w-4" />
        <span class="text-xs font-semibold uppercase tracking-wide">{{ $label }}</span>
    </div>
    <div class="mt-1.5 text-sm text-warning-ink">{{ $slot }}</div>
</div>
