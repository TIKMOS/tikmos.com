@props([
    'label' => null,
    'description' => null,
])

{{-- Interrupteur on/off lié à wire:model. Accessible (role switch). --}}
<label {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'flex items-center justify-between gap-4 cursor-pointer select-none']) }}>
    @if ($label)
        <span class="min-w-0">
            <span class="block text-sm font-medium text-content">{{ $label }}</span>
            @if ($description)<span class="block text-xs text-muted">{{ $description }}</span>@endif
        </span>
    @endif
    <span class="relative inline-flex shrink-0">
        <input type="checkbox" {{ $attributes->whereStartsWith('wire:model') }} class="peer sr-only" />
        <span class="h-6 w-11 rounded-full bg-tk-300 transition-colors peer-checked:bg-ink peer-focus-visible:ring-4 peer-focus-visible:ring-ink/15"></span>
        <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5"></span>
    </span>
</label>
