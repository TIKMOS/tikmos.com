<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderReceived;
use App\Settings\GeneralSettings;
use App\Settings\ShopSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderService
{
    public function __construct(private CartService $cart) {}

    /**
     * Crée une commande à partir du panier courant (transaction). Recalcul serveur.
     *
     * @param  array{contact_name: string, contact_phone: string, delivery_address: string, delivery_city?: string|null, notes?: string|null, courier_id?: int|null}  $data
     */
    public function place(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data): Order {
            $items = $this->cart->items();

            abort_if($items->isEmpty(), 422, 'Votre panier est vide.');

            $subtotal = (int) $items->sum('line_total');
            $deliveryFee = app(ShopSettings::class)->default_delivery_fee;
            $total = $subtotal + $deliveryFee;

            $order = Order::create([
                'number' => $this->generateNumber(),
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'payment_method' => PaymentMethod::Cod,
                'payment_status' => OrderPaymentStatus::Unpaid,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'delivery_address' => $data['delivery_address'],
                'delivery_city' => $data['delivery_city'] ?? null,
                'courier_id' => $data['courier_id'] ?? null,
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

                // Décrément du stock si géré.
                if ($item['product']->stock !== null) {
                    $item['product']->decrement('stock', $item['qty']);
                }
            }

            $this->cart->clear();

            // Alerte équipe (email de contact) — hors transaction critique mais suffisant ici.
            $teamEmail = app(GeneralSettings::class)->emails['contact'] ?? null;
            if ($teamEmail) {
                Notification::route('mail', $teamEmail)->notify(new OrderReceived($order));
            }

            return $order;
        });
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $seq = Order::whereYear('created_at', $year)->count() + 1;

        return sprintf('TKM-CMD-%d-%05d', $year, $seq);
    }
}
