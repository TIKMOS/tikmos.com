@props(['subtotal', 'fee', 'pricing'])

<dl class="mt-4 space-y-2 border-t border-line pt-4 text-sm">
    <div class="flex justify-between gap-3"><dt class="text-muted">Sous-total</dt><dd class="font-medium text-content">{{ number_format($subtotal, 0, ',', ' ') }} FCFA</dd></div>
    <div class="flex justify-between gap-3"><dt class="text-muted">Livraison</dt><dd class="font-medium text-content">{{ match ($pricing) { \App\Enums\DeliveryPricing::Free => 'Gratuite', \App\Enums\DeliveryPricing::Fixed => number_format($fee, 0, ',', ' ').' FCFA', default => 'À déterminer' } }}</dd></div>
    <div class="flex justify-between gap-3 border-t border-line pt-2 text-base"><dt class="font-bold text-content">{{ $pricing === \App\Enums\DeliveryPricing::Unknown ? 'Total hors livraison' : 'Total' }}</dt><dd class="shrink-0 font-display font-bold text-content">{{ number_format($subtotal + $fee, 0, ',', ' ') }} FCFA</dd></div>
</dl>
@if ($pricing === \App\Enums\DeliveryPricing::Unknown)
    <x-tk.delivery-notice />
@endif
