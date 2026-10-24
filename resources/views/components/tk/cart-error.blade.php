@props(['showCartLink' => false])

@error('cart')
    <div role="alert" {{ $attributes->class(['rounded-card bg-danger-soft p-4 text-sm text-danger-ink']) }}>
        <p>{{ $message }}</p>
        @if ($showCartLink)
            <a href="{{ route('cart') }}" wire:navigate class="mt-2 inline-flex font-medium underline">Modifier mon panier</a>
        @endif
    </div>
@enderror
