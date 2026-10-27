@props(['message' => 'Les frais de livraison ne sont pas inclus. Notre équipe doit vous communiquer les frais et le montant final, puis obtenir votre accord avant tout paiement.'])

<div {{ $attributes->class('mt-4 rounded-tile border border-line bg-sand p-4 text-sm leading-relaxed text-content') }}>
    <p>{{ $message }}</p>
    <a href="{{ route('contact') }}" wire:navigate class="mt-2 inline-block min-h-11 content-center font-medium underline underline-offset-2">Contacter l’équipe pour la livraison</a>
</div>
