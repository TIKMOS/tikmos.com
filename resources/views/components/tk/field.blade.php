@props([
    'label' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="block text-[13px] font-medium text-content">
            {{ $label }}
            @if ($required)<span class="text-danger">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $error)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="flex items-center gap-1 text-xs font-medium text-danger">
            <x-lucide-circle-alert class="h-3.5 w-3.5" />
            {{ $error }}
        </p>
    @endif
</div>
