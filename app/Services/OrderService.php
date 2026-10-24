<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderReceived;
use App\Settings\GeneralSettings;
use App\Settings\ShopSettings;
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
    public function place(User $user, array $data, PaymentMethod $method = PaymentMethod::Cod): Order
    {
        $order = DB::transaction(function () use ($user, $data, $method): Order {
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
            $deliveryFee = app(ShopSettings::class)->default_delivery_fee;
            $total = $subtotal + $deliveryFee;

            $order = Order::create([
                'number' => $this->generateNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_method' => $method,
                'payment_status' => OrderPaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'delivery_address' => $data['delivery_address'],
                'delivery_city' => $data['delivery_city'] ?? null,
                'notes' => $data['notes'] ?? null,
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
        $this->cart->clear();
        $teamEmail = app(GeneralSettings::class)->emails['contact'] ?? null;
        if ($teamEmail) {
            Notification::route('mail', $teamEmail)->notify(new OrderReceived($order));
        }

        return $order;
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $seq = Order::whereYear('created_at', $year)->count() + 1;

        return sprintf('TKM-CMD-%d-%05d', $year, $seq);
    }
}
