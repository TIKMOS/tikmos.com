@props([
    'variant' => 'primary', // primary | gold | accent | secondary | ghost | danger | whatsapp
    'size' => 'md',         // sm | md | lg
    'href' => null,
    'type' => 'button',
    'icon' => null,         // nom d'icône lucide (ex. 'arrow-right')
    'iconRight' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded-btn transition duration-200 active:scale-[0.97] disabled:opacity-50 disabled:pointer-events-none disabled:active:scale-100 select-none whitespace-nowrap';

    $sizes = [
        'sm' => 'text-sm px-4 min-h-[40px]',
        'md' => 'text-[15px] px-5 min-h-[46px]',
        'lg' => 'text-base px-6 min-h-[50px]',
    ];

    $variants = [
        // CTA principal — aplat encre (un par zone)
        'primary' => 'bg-ink text-paper hover:bg-tk-800',
        // Emphase premium — blanc + bordure OR champagne (signature luxe)
        'gold' => 'bg-paper text-gold-600 border border-gold hover:bg-gold-100',
        // « accent » = emphase sobre : blanc + bordure encre (compat)
        'accent' => 'bg-paper text-content border border-ink hover:bg-tk-100',
        'secondary' => 'bg-paper text-content border border-tk-300 hover:border-ink hover:bg-tk-50',
        'ghost' => 'bg-transparent text-content hover:bg-tk-100',
        'danger' => 'bg-danger text-white hover:opacity-90',
        // WhatsApp (vert imposé)
        'whatsapp' => 'bg-whatsapp text-white hover:opacity-90',
    ];

    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
    $iconSize = $size === 'lg' ? 'w-5 h-5' : 'w-[18px] h-[18px]';
@endphp

@if ($href)
    <a href="{{ $href }}" wire:navigate {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && ! $iconRight)<x-dynamic-component :component="'lucide-'.$icon" :class="$iconSize" />@endif
        <span>{{ $slot }}</span>
        @if ($icon && $iconRight)<x-dynamic-component :component="'lucide-'.$icon" :class="$iconSize" />@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && ! $iconRight)<x-dynamic-component :component="'lucide-'.$icon" :class="$iconSize" wire:loading.remove />@endif
        <svg wire:loading style="display:none" class="animate-spin {{ $iconSize }}" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-25" />
            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
        </svg>
        <span>{{ $slot }}</span>
        @if ($icon && $iconRight)<x-dynamic-component :component="'lucide-'.$icon" :class="$iconSize" />@endif
    </button>
@endif
