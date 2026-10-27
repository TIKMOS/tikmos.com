<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DeliveryPricing;
use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderReceived;
use App\Settings\GeneralSettings;
use App\Settings\PaymentSettings;
use App\Settings\ShopSettings;
use App\Support\OrderNumberAllocator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private CartService $cart) {}

    /**
     * Crée une commande à partir du panier courant (transaction). Recalcul serveur.
     *
     * @param  array{contact_name: string, contact_phone: string, delivery_address: string, delivery_city?: string|null, notes?: string|null}  $data
     */
    public function place(User $user, array $data, PaymentMethod $method = PaymentMethod::Cod, ?string $checkoutToken = null, ?string $deliveryQuoteKey = null): Order
    {
        $order = DB::transaction(function () use ($user, $data, $method, $checkoutToken, $deliveryQuoteKey): Order {
            // Écriture et verrou annuel avant toute lecture (SQLite DEFERRED compris).
            $numbers = app(OrderNumberAllocator::class);
            $year = $numbers->lockCurrentYear();
            // Sérialise le rejeu du même checkout avant la lecture du panier.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($checkoutToken !== null && $existing = Order::where('checkout_token', $checkoutToken)->first()) {
                abort_unless($existing->user_id === $user->id, 403);

                return $existing;
            }
            $shop = app(ShopSettings::class);
            $pricing = $shop->deliveryPricing();
            if ($deliveryQuoteKey !== null && ! hash_equals($shop->deliveryQuoteKey(), $deliveryQuoteKey)) {
                throw ValidationException::withMessages(['delivery' => 'Les conditions de livraison ont changé. Vérifiez le nouveau récapitulatif, puis confirmez à nouveau.']);
            }
            if ($method === PaymentMethod::Online && $pricing === DeliveryPricing::Unknown) {
                throw ValidationException::withMessages(['payment_method' => 'Le paiement en ligne est indisponible tant que les frais de livraison et le montant final ne sont pas validés. Choisissez la commande avec validation commerciale ou contactez notre équipe.']);
            }
            if ($method === PaymentMethod::Online && ! app(PaymentSettings::class)->onlineEnabled()) {
                throw ValidationException::withMessages(['payment_method' => 'Le paiement en ligne est indisponible. Choisissez un autre mode ou contactez l’équipe.']);
            }
            $items = $this->cart->items();

            abort_if($items->isEmpty(), 422, 'Votre panier est vide.');

            // Ordre de verrouillage stable ; les données du panier ne réservent aucun stock.
            $products = Product::active()->whereIn('id', $items->pluck('product.id'))
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $items = $items->map(function (array $item) use ($products): array {
                $product = $products->get($item['product']->id);
                if ($product === null) {
                    throw ValidationException::withMessages(['cart' => 'Un produit n’est plus disponible. Modifiez votre panier.']);
                }

                return [...$item, 'product' => $product, 'line_total' => $product->price * $item['qty']];
            });

            $quantities = [];
            foreach ($items as $item) {
                $id = $item['product']->id;
                $quantities[$id] = ($quantities[$id] ?? 0) + $item['qty'];
            }
            foreach ($products as $product) {
                $this->cart->assertStock($product, $quantities[$product->id]);
            }

            $subtotal = (int) $items->sum('line_total');
            $deliveryFee = $shop->deliveryFee();
            $total = $subtotal + $deliveryFee;

            $order = Order::create([
                'number' => $numbers->nextForYear($year),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_method' => $method,
                'payment_status' => OrderPaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'delivery_pricing' => $pricing->value,
                'total' => $total,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'delivery_address' => $data['delivery_address'],
                'delivery_city' => $data['delivery_city'] ?? null,
                'notes' => $data['notes'] ?? null,
                'checkout_token' => $checkoutToken,
                'reservation_expires_at' => $method === PaymentMethod::Online ? now()->addMinutes(15) : null,
                'stock_reservation' => $method === PaymentMethod::Online
                    ? $products->filter(fn (Product $product): bool => $product->stock !== null)
                        ->mapWithKeys(fn (Product $product): array => [$product->id => $quantities[$product->id]])->all()
                    : null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'name' => $item['product']->name,
                    'size' => $item['size'] ?? null,
                    'unit_price' => $item['product']->price,
                    'quantity' => $item['qty'],
                    'line_total' => $item['line_total'],
                ]);

            }

            // Un seul décrément par produit, après vérification de tous les cumuls sous verrou.
            foreach ($products as $product) {
                if ($product->stock !== null) {
                    $product->decrement('stock', $quantities[$product->id]);
                }
            }

            return $order;
        }, attempts: 3);

        // La session et les notifications ne sont pas modifiées lors d'un refus/rollback.
        if ($order->wasRecentlyCreated) {
            $this->cart->clear();
        }
        $teamEmail = $order->wasRecentlyCreated ? (app(GeneralSettings::class)->emails['contact'] ?? null) : null;
        if ($teamEmail) {
            Notification::route('mail', $teamEmail)->notify(new OrderReceived($order));
        }

        return $order;
    }
}
