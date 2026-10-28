@props([
    'label' => '',
    'value' => '',
    'icon' => null,
    'tint' => null, // null | sky | mint | lilac | peach
])

@php
    $tints = ['sky' => 'bg-indigo-50', 'mint' => 'bg-sage', 'lilac' => 'bg-mist', 'peach' => 'bg-sand'];
    $cardBg = $tint && isset($tints[$tint]) ? $tints[$tint].' border-line/60' : 'bg-surface border-line';
    $iconBg = $tint && isset($tints[$tint]) ? 'bg-ink text-paper' : 'bg-tk-100 text-tk-600';
@endphp

<div {{ $attributes->merge(['class' => 'rounded-card border p-5 transition-all hover:-translate-y-0.5 hover:shadow-pop '.$cardBg]) }}>
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-[13px] font-medium text-muted">{{ $label }}</p>
            <p class="mt-1 font-display text-3xl font-bold leading-none text-content">{{ $value }}</p>
        </div>
        @if ($icon)
            <span class="grid h-9 w-9 place-items-center rounded-tile {{ $iconBg }}">
                <x-dynamic-component :component="'lucide-'.$icon" class="h-[18px] w-[18px]" />
            </span>
        @endif
    </div>
    @if ($slot->isNotEmpty())
        <div class="mt-2 text-xs text-muted">{{ $slot }}</div>
    @endif
</div>
