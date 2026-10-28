@props([
    'type' => 'text',
    'icon' => null,
    'invalid' => false,
])
@aware(['for' => null, 'hint' => null, 'error' => null, 'required' => false])

@php
    $invalid = $invalid || (bool) $error;
    $descriptions = array_filter([$attributes->get('aria-describedby'), $for && $hint && ! $error ? $for.'-hint' : null, $for && $error ? $for.'-error' : null]);
    $describedBy = implode(' ', array_unique(preg_split('/\s+/', trim(implode(' ', $descriptions)), flags: PREG_SPLIT_NO_EMPTY)));
    $attributes = $attributes->except('aria-describedby')->merge([
        'id' => $for, 'aria-describedby' => $describedBy ?: null,
        'aria-invalid' => $invalid ? 'true' : 'false',
        'required' => $required, 'aria-required' => $required ? 'true' : null,
    ]);
    $border = $invalid ? 'border-danger focus:border-danger' : 'border-control-line focus:border-ink';
    $classes = 'w-full rounded-input border bg-surface text-content placeholder:text-muted min-h-[48px] px-4 text-[15px] transition-colors focus:ring-4 focus:ring-ink/10 '.$border;
@endphp

@if ($icon)
    <div class="relative">
        <x-dynamic-component :component="'lucide-'.$icon" class="pointer-events-none absolute left-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-tk-400" />
        <input type="{{ $type }}" {{ $attributes->merge(['class' => $classes.' pl-11']) }} />
    </div>
@else
    <input type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} />
@endif
