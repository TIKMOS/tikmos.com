<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Payments\Enums\PaymentProvider;
use App\Services\OrderReservationService;
use App\Services\PaymentService;
use App\Settings\PaymentSettings;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public Order $order;

    public string $paymentMessage = '';

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === auth()->id(), 403);
        app(OrderReservationService::class)->expire($order);
        $order->refresh();
        $this->order = $order->load('items');
    }

    public function resumePayment(PaymentService $payments): void
    {
        $this->resetValidation('payment');
        try {
            $payment = $payments->resumeOrder($this->order, auth()->user());
        } finally {
            $this->order->refresh()->load('items');
        }
        $this->paymentMessage = 'Le résultat sera vérifié côté serveur. Ne lancez pas un second paiement si vous avez déjà été débité.';
        $this->dispatch('open-order-payment', reference: $payment->reference, amount: $payment->amount,
            orderNumber: $this->order->number, name: $this->order->contact_name, phone: $this->order->contact_phone);
    }

    public function widgetInterrupted(string $reason = 'closed'): void
    {
        abort_unless($this->order->fresh()->user_id === auth()->id(), 403);
        // Fermeture/échec côté navigateur ne prouve aucun résultat bancaire.
        app(OrderReservationService::class)->expire($this->order);
        $this->order->refresh()->load('items');
        $this->paymentMessage = 'Aucun paiement confirmé par cette fenêtre. Si vous avez été débité, ne payez pas une seconde fois : contactez l’équipe avec votre référence de commande. Sinon, vous pouvez reprendre avant l’échéance.';
    }

    public function confirmOnline(string $reference, string $transactionId, PaymentService $payments): void
    {
        abort_unless($this->order->fresh()->user_id === auth()->id(), 403);
        abort_if($transactionId === '' || strlen($transactionId) > 255, 422);
        $payment = $this->order->payments()->where('provider', PaymentProvider::Kkiapay->value)
            ->where('reference', $reference)->firstOrFail();
        $payments->verify($payment, $transactionId);
        $this->order->refresh()->load('items');
        $this->paymentMessage = $this->order->payment_review_required
            ? 'Paiement reçu après libération du stock : vérification manuelle nécessaire. Ne payez pas à nouveau.'
            : ($this->order->payment_status === \App\Enums\OrderPaymentStatus::Paid
                ? 'Paiement confirmé. Merci pour votre commande.'
                : 'Vérification non concluante. Aucun succès confirmé. Si vous avez été débité, contactez l’équipe avant tout nouvel essai.');
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $settings = app(PaymentSettings::class);
        $config = array_merge((array) config('payments.gateways.kkiapay'), $settings->kkiapayConfig());

        return ['onlineEnabled' => $settings->onlineEnabled(),
            'widgetConfig' => ['key' => $config['public_key'] ?? '', 'sandbox' => $settings->kkiapay_sandbox]];
    }

    public function confirmReceived(): void
    {
        abort_unless($this->order->user_id === auth()->id(), 403);
        abort_unless($this->order->status === OrderStatus::Delivered, 422);

        $this->order->update(['received_confirmed_at' => now()]);
        $this->dispatch('tk-toast', type: 'success', message: 'Merci ! Réception confirmée.');
    }
}; ?>

<div>
    <a href="{{ route('app.orders') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Mes achats</a>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-extrabold text-content">{{ $order->number }}</h1>
            <p class="mt-1 text-muted">{{ $order->created_at->translatedFormat('d F Y') }} · {{ $order->payment_method->label() }}</p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <x-tk.badge :tone="$order->status->tone()" dot>{{ $order->status->label() }}</x-tk.badge>
            <x-tk.badge :tone="$order->payment_status->tone()">{{ $order->payment_status->label() }}</x-tk.badge>
        </div>
    </div>

    @if ($order->payment_method === \App\Enums\PaymentMethod::Online)
        <section class="mt-6 rounded-card border border-line bg-surface p-5 sm:p-6" aria-labelledby="payment-heading"
            x-data="tkOrderPayment($wire, @js($widgetConfig))" x-on:open-order-payment.window="open($event.detail)">
            <h2 id="payment-heading" class="font-display text-xl font-bold text-content">Paiement de votre commande</h2>
            @if ($order->payment_review_required)
                <p class="mt-2 font-medium text-content">Paiement reçu — vérification nécessaire</p>
                <p class="mt-2 text-sm text-muted">Le stock a été libéré ou la commande annulée avant la confirmation du paiement. L’équipe doit vérifier la disponibilité et convenir de la suite avec vous. Ne payez pas à nouveau. Aucun remboursement automatique n’a été effectué.</p>
                <x-tk.button :href="route('contact')" variant="secondary" class="mt-4">Contacter l’équipe</x-tk.button>
            @elseif ($order->payment_status === \App\Enums\OrderPaymentStatus::Paid)
                <p class="mt-2 text-sm text-success-ink">Votre paiement est confirmé.</p>
            @elseif ($order->canResumePayment())
                <p class="mt-2 text-sm text-muted">Vos articles sont réservés jusqu’au {{ $order->reservation_expires_at->timezone(config('app.timezone'))->format('d/m/Y à H:i') }} ({{ config('app.timezone') }}). Fermer la fenêtre ne supprime pas votre commande et ne prolonge pas ce délai.</p>
                @if ($onlineEnabled)
                    <x-tk.button wire:click="resumePayment" wire:loading.attr="disabled" wire:target="resumePayment,confirmOnline,widgetInterrupted" x-bind:disabled="busy" class="mt-4 w-full sm:w-auto">Reprendre le paiement</x-tk.button>
                    <button type="button" x-show="busy" x-cloak x-on:click="interrupted()" class="mt-3 block min-h-[44px] text-sm font-medium text-content underline underline-offset-4">J’ai fermé la fenêtre de paiement</button>
                @else
                    <p class="mt-3 text-sm text-muted">Le paiement en ligne est momentanément indisponible. Contactez l’équipe si vous avez déjà été débité.</p>
                    <x-tk.button :href="route('contact')" variant="secondary" class="mt-4">Contacter l’équipe</x-tk.button>
                @endif
            @else
                <p class="mt-2 font-medium text-content">{{ $order->reservation_expires_at ? 'Réservation expirée ou commande annulée' : 'Commande antérieure : vérification nécessaire' }}</p>
                <p class="mt-2 text-sm text-muted">Le paiement ne peut plus être relancé ici. Si vous avez déjà été débité, contactez l’équipe avec votre numéro de commande. Sinon, contactez-nous pour vérifier la disponibilité avant une nouvelle commande.</p>
                <x-tk.button :href="route('contact')" variant="secondary" class="mt-4">Contacter l’équipe</x-tk.button>
            @endif
            <div class="mt-3 text-sm text-content" role="status" aria-live="polite">
                @if ($paymentMessage)<p>{{ $paymentMessage }}</p>@endif
                <p x-text="message"></p>
            </div>
            @error('payment')<p role="alert" class="mt-3 text-sm text-danger">{{ $message }}</p>@enderror
        </section>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Suivi --}}
            <x-tk.card>
                <x-tk.kicker>Suivi</x-tk.kicker>
                <div class="mt-5"><x-tk.stepper :steps="$order->timelineSteps()" /></div>

                @if ($order->status === \App\Enums\OrderStatus::Delivered && ! $order->received_confirmed_at)
                    <div class="mt-5 rounded-tile bg-mint/40 p-4">
                        <p class="text-sm font-medium text-content">Avez-vous bien reçu votre commande ?</p>
                        <x-tk.button wire:click="confirmReceived" variant="primary" size="sm" icon="check" class="mt-3">Confirmer la réception</x-tk.button>
                    </div>
                @elseif ($order->received_confirmed_at)
                    <p class="mt-4 inline-flex items-center gap-1.5 text-sm text-success-ink"><x-lucide-circle-check class="h-4 w-4" /> Réception confirmée le {{ $order->received_confirmed_at->format('d/m/Y') }}</p>
                @endif
            </x-tk.card>

            {{-- Articles --}}
            <x-tk.card>
                <x-tk.kicker>Articles</x-tk.kicker>
                <div class="mt-4 divide-y divide-line">
                    @foreach ($order->items as $item)
                        <div class="flex items-center justify-between gap-3 py-3 text-sm" wire:key="oi-{{ $item->id }}">
                            <span class="text-content">{{ $item->quantity }} × {{ $item->name }}@if ($item->size) <span class="text-muted">· Taille {{ $item->size }}</span>@endif</span>
                            <span class="font-medium text-content">{{ $item->lineTotalLabel() }}</span>
                        </div>
                    @endforeach
                </div>
                <dl class="mt-3 space-y-1.5 border-t border-line pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Sous-total</dt><dd class="text-content">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Livraison</dt><dd class="text-content">{{ $order->delivery_fee > 0 ? number_format($order->delivery_fee, 0, ',', ' ').' FCFA' : 'À convenir' }}</dd></div>
                    <div class="flex justify-between text-base"><dt class="font-bold text-content">Total</dt><dd class="font-display font-bold text-content">{{ $order->totalLabel() }}</dd></div>
                </dl>
            </x-tk.card>
        </div>

        <div>
            <x-tk.card>
                <x-tk.kicker>Livraison</x-tk.kicker>
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="text-muted">Destinataire</dt><dd class="font-medium text-content">{{ $order->contact_name }}</dd></div>
                    <div><dt class="text-muted">Téléphone</dt><dd class="font-medium text-content">{{ $order->contact_phone }}</dd></div>
                    <div><dt class="text-muted">Adresse</dt><dd class="text-content">{{ $order->delivery_address }}{{ $order->delivery_city ? ', '.$order->delivery_city : '' }}</dd></div>
                </dl>
            </x-tk.card>
        </div>
    </div>
</div>
