@props([
    'max' => 5,
    'readonly' => false,
    'value' => null, // pour l'affichage en lecture seule
    'size' => 'md',   // sm | md | lg
])

@php
    $dim = ['sm' => 'h-4 w-4', 'md' => 'h-6 w-6', 'lg' => 'h-8 w-8'][$size] ?? 'h-6 w-6';
    $wireModel = $attributes->wire('model')->value();
@endphp

@if ($readonly)
    {{-- Affichage : étoiles pleines selon $value --}}
    <div {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'inline-flex items-center gap-0.5']) }} aria-label="Note {{ $value }}/{{ $max }}">
        @for ($i = 1; $i <= $max; $i++)
            <x-lucide-star @class([$dim, 'fill-ink text-ink' => $i <= (int) $value, 'text-control-line' => $i > (int) $value]) />
        @endfor
    </div>
@else
    {{-- Saisie interactive liée à wire:model --}}
    <div x-data="{ value: @entangle($attributes->wire('model')), hover: 0 }"
         {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'inline-flex items-center gap-1']) }}
         role="radiogroup" aria-label="Note">
        @for ($i = 1; $i <= $max; $i++)
            <button type="button"
                    @click="value = {{ $i }}" @mouseenter="hover = {{ $i }}" @mouseleave="hover = 0"
                    class="rounded p-0.5 transition-transform hover:scale-110 focus-visible:ring-4 focus-visible:ring-ink/15"
                    :aria-checked="value === {{ $i }}" role="radio" aria-label="{{ $i }} étoile(s)">
                <x-lucide-star class="{{ $dim }} transition-colors"
                    ::class="(hover ? {{ $i }} <= hover : {{ $i }} <= value) ? 'fill-ink text-ink' : 'text-control-line'" />
            </button>
        @endfor
    </div>
@endif
