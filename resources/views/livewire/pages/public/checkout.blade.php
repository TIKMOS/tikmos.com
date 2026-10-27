<?php

use App\Enums\DeliveryPricing;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Payments\Enums\PaymentProvider;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Settings\PaymentSettings;
use App\Settings\ShopSettings;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('components.layouts.public')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $contact_name = '';

    #[Validate('required|string|max:30')]
    public string $contact_phone = '';

    #[Validate('required|string|max:500')]
    public string $delivery_address = '';

    #[Validate('nullable|string|max:120')]
    public string $delivery_city = '';

    #[Validate('nullable|string|max:1000')]
    public string $notes = '';

    public string $payment_method = 'cod';

    #[Locked]
    public string $checkoutToken;

    #[Locked]
    public string $deliveryQuoteKey;

    public function mount(CartService $cart, PaymentSettings $payments): void
    {
        $this->checkoutToken = (string) Str::uuid();
        $this->deliveryQuoteKey = app(ShopSettings::class)->deliveryQuoteKey();
        if ($cart->isEmpty()) {
            $this->redirect(route('cart'), navigate: true);

            return;
        }
        $user = auth()->user();
        $this->contact_name = $user->name;
        $this->contact_phone = (string) $user->phone;
        $this->delivery_city = (string) $user->city;

        // Mode par défaut selon les réglages (online prioritaire si disponible).
        $this->payment_method = $payments->onlineEnabled() && app(ShopSettings::class)->deliveryPricing() !== DeliveryPricing::Unknown ? 'online' : 'cod';
    }

    public function place(OrderService $orders, PaymentService $payments, PaymentSettings $settings): void
    {
        $this->resetValidation(['cart', 'delivery', 'payment_method']);
        $data = $this->validate();

        $method = $this->resolveMethod($settings);
        try {
            $order = $orders->place(auth()->user(), $data, $method, $this->checkoutToken, $this->deliveryQuoteKey);
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['delivery'])) {
                $this->deliveryQuoteKey = app(ShopSettings::class)->deliveryQuoteKey();
                $this->dispatch('delivery-changed');
            }
            throw $exception;
        }
        $this->dispatch('cart-updated');

        if ($method === PaymentMethod::Online) {
            // Le détail persistant permet la reprise après fermeture/rafraîchissement.
            $payment = $payments->open($order, PaymentProvider::Kkiapay);
            $this->redirect(route('app.orders.show', $order->number), navigate: true);

            return;
        }

        session()->flash('toast', ['type' => 'success', 'message' => ($order->deliveryIsFinal() ? 'Commande enregistrée. Numéro : ' : 'Commande enregistrée — livraison et montant final à valider. Numéro : ').$order->number]);
        $this->redirect(route('app.orders.show', $order->number), navigate: true);
    }

    /** Vérifie la transaction après retour du widget (source de vérité serveur). */
    public function confirmOnline(string $reference, string $transactionId, PaymentService $payments): void
    {
        $payment = Payment::where('reference', $reference)->firstOrFail();

        abort_unless($payment->payable instanceof Order && $payment->payable->user_id === auth()->id(), 403);

        if ($payments->verify($payment, $transactionId)) {
            session()->flash('toast', ['type' => 'success', 'message' => 'Paiement confirmé ! Commande '.$payment->payable->number]);
            $this->redirect(route('app.orders.show', $payment->payable->number), navigate: true);

            return;
        }

        $this->redirect(route('app.orders.show', $payment->payable->number), navigate: true);
    }

    private function resolveMethod(PaymentSettings $settings): PaymentMethod
    {
        if ($this->payment_method === 'online' && $settings->onlineEnabled()) {
            return PaymentMethod::Online;
        }
        if ($this->payment_method === 'cod' && $settings->codEnabled()) {
            return PaymentMethod::Cod;
        }

        throw ValidationException::withMessages(['payment_method' => 'Le mode de paiement choisi est indisponible. Choisissez un mode proposé ou contactez l’équipe.']);
    }

    /** @return array<string, mixed> */
    public function with(): array
    {
        $cart = app(CartService::class);
        $settings = app(PaymentSettings::class);

        return [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => app(ShopSettings::class)->deliveryFee(),
            'deliveryPricing' => app(ShopSettings::class)->deliveryPricing(),
            'onlineEnabled' => $settings->onlineEnabled() && app(ShopSettings::class)->deliveryPricing() !== DeliveryPricing::Unknown,
            'codEnabled' => $settings->codEnabled(),
        ];
    }
}; ?>

<div>
    @push('meta')<x-tk.seo title="Commander" />@endpush

    <x-tk.section>
        <a href="{{ route('cart') }}" wire:navigate class="inline-flex items-center gap-1 text-sm font-medium text-muted hover:text-content"><x-lucide-arrow-left class="h-4 w-4" /> Panier</a>
        <h1 class="mt-3 font-display text-4xl font-black tracking-[-0.02em] text-content md:text-5xl">Finaliser la commande</h1>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1.4fr_1fr]">
            {{-- Récap --}}
            <div class="min-w-0 order-first lg:order-last">
                <div class="rounded-card border border-line bg-surface p-6">
                    <h2 class="font-display text-lg font-bold text-content">Votre commande</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($items as $item)
                            <div class="flex items-center gap-3 text-sm" wire:key="co-{{ \Illuminate\Support\Str::slug($item['key']) }}">
                                <span class="grid h-6 min-w-6 place-items-center rounded-full bg-tk-100 px-1 text-xs font-bold text-content">{{ $item['qty'] }}</span>
                                <span class="min-w-0 flex-1 truncate text-content">{{ $item['product']->name }}@if ($item['size']) · Taille {{ $item['size'] }}@endif</span>
                                <span class="font-medium text-content">{{ number_format($item['line_total'], 0, ',', ' ') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <x-tk.delivery-summary :subtotal="$subtotal" :fee="$deliveryFee" :pricing="$deliveryPricing" />
                </div>
            </div>

            {{-- Formulaire --}}
            <form wire:submit="place" class="min-w-0 space-y-5">
                <x-tk.admin-section title="Livraison" description="Où devons-nous livrer votre commande ?" icon="map-pin">
                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-tk.field label="Nom complet" :error="$errors->first('contact_name')"><x-tk.input wire:model="contact_name" icon="user" /></x-tk.field>
                            <x-tk.field label="Téléphone" :error="$errors->first('contact_phone')"><x-tk.phone wire:model="contact_phone" class="min-w-0" :invalid="$errors->has('contact_phone')" /></x-tk.field>
                        </div>
                        <x-tk.field label="Adresse de livraison" :error="$errors->first('delivery_address')">
                            <textarea wire:model="delivery_address" rows="2" placeholder="Quartier, rue, repère…" class="w-full rounded-input border border-tk-300 bg-surface px-4 py-3 text-[15px] text-content focus:border-ink focus:outline-none focus:ring-4 focus:ring-ink/10"></textarea>
                        </x-tk.field>
                        <x-tk.field label="Ville" :error="$errors->first('delivery_city')"><x-tk.input wire:model="delivery_city" icon="map-pin" /></x-tk.field>
                        <x-tk.field label="Notes (optionnel)" :error="$errors->first('notes')"><x-tk.input wire:model="notes" placeholder="Précisions de livraison…" /></x-tk.field>
                    </div>
                </x-tk.admin-section>

                <x-tk.admin-section title="Paiement" icon="wallet">
                    <div class="space-y-3">
                        @if (! $onlineEnabled)
                            <p class="text-sm text-muted">{{ $deliveryPricing === \App\Enums\DeliveryPricing::Unknown ? 'Le paiement en ligne est indisponible tant que les frais de livraison et le montant final ne sont pas validés.' : 'Le paiement en ligne est actuellement indisponible.' }}</p>
                        @endif
                        @error('payment_method')<p role="alert" class="text-sm text-danger">{{ $message }}</p>@enderror
                        @if ($onlineEnabled)
                            <label class="flex cursor-pointer items-center gap-3 rounded-tile border-2 p-4 transition"
                                :class="$wire.payment_method === 'online' ? 'border-ink bg-sky/40' : 'border-line hover:border-tk-300'">
                                <input type="radio" wire:model="payment_method" value="online" class="sr-only" />
                                <x-lucide-smartphone class="h-6 w-6 text-content" />
                                <span><span class="block font-medium text-content">Paiement en ligne</span><span class="block text-sm text-muted">Mobile Money (MTN, Moov) ou carte bancaire via KKiaPay — sécurisé.</span></span>
                                <x-lucide-check-circle-2 class="ml-auto h-5 w-5 text-content" x-show="$wire.payment_method === 'online'" />
                            </label>
                        @endif

                        @if ($codEnabled)
                            <label class="flex cursor-pointer items-center gap-3 rounded-tile border-2 p-4 transition"
                                :class="$wire.payment_method === 'cod' ? 'border-ink bg-sky/40' : 'border-line hover:border-tk-300'">
                                <input type="radio" wire:model="payment_method" value="cod" class="sr-only" />
                                <x-lucide-truck class="h-6 w-6 text-content" />
                                <span><span class="block font-medium text-content">Paiement à la livraison</span><span class="block text-sm text-muted">Réglez en espèces ou Mobile Money à la réception.</span></span>
                                <x-lucide-check-circle-2 class="ml-auto h-5 w-5 text-content" x-show="$wire.payment_method === 'cod'" />
                            </label>
                        @endif
                    </div>
                </x-tk.admin-section>

                <x-tk.cart-error :show-cart-link="true" />
                @error('delivery')<p id="delivery-change-error" role="alert" tabindex="-1" x-data x-init="$el.focus()" class="text-sm font-medium text-danger">{{ $message }}</p>@enderror
                @if (! $onlineEnabled && ! $codEnabled)
                    <p class="text-sm text-content">Aucun mode de paiement n’est disponible pour cette commande. <a href="{{ route('contact') }}" class="font-medium underline">Contactez notre équipe</a> avant de continuer.</p>
                @endif

                <x-tk.button type="submit" variant="primary" size="lg" icon="check" class="w-full sm:w-auto" wire:loading.attr="disabled" wire:target="place,confirmOnline" :disabled="! $onlineEnabled && ! $codEnabled">
                    <span wire:loading.remove wire:target="place,confirmOnline">{{ $deliveryPricing === \App\Enums\DeliveryPricing::Unknown ? 'Transmettre pour validation' : 'Confirmer ma commande' }}</span>
                    <span wire:loading wire:target="place,confirmOnline">Traitement…</span>
                </x-tk.button>
            </form>


        </div>
    </x-tk.section>

</div>
