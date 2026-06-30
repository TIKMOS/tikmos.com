<?php

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public int $count = 0;

    public function mount(CartService $cart): void
    {
        $this->count = $cart->count();
    }

    #[On('cart-updated')]
    public function refresh(CartService $cart): void
    {
        $this->count = $cart->count();
    }
}; ?>

<a href="{{ url('/panier') }}" wire:navigate class="relative grid h-10 w-10 place-items-center rounded-tile text-content transition-colors hover:bg-tk-50" aria-label="Panier ({{ $count }})">
    <x-lucide-shopping-cart class="h-5 w-5" />
    @if ($count > 0)
        <span class="absolute right-0.5 top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-ink px-1 text-[10px] font-bold leading-none text-paper">{{ $count }}</span>
    @endif
</a>
