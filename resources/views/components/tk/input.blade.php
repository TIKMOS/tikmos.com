@props([
    'type' => 'text',
    'icon' => null,
    'invalid' => false,
])

@php
    $border = $invalid ? 'border-danger focus:border-danger' : 'border-tk-300 focus:border-ink';
    $classes = 'w-full rounded-input border bg-surface text-content placeholder:text-tk-400 min-h-[48px] px-4 text-[15px] transition-colors focus:outline-none focus:ring-4 focus:ring-ink/10 '.$border;
@endphp

@if ($icon)
    <div class="relative">
        <x-dynamic-component :component="'lucide-'.$icon" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-tk-400" />
        <input type="{{ $type }}" {{ $attributes->merge(['class' => $classes.' pl-11']) }} />
    </div>
@else
    <input type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} />
@endif
