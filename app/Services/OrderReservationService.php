<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderPaymentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderReservationService
{
    public function expire(Order $order): bool
    {
        return DB::transaction(function () use ($order): bool {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $this->releaseIfDue($locked);
        }, attempts: 3);
    }

    /** L'appelant détient le verrou de commande dans sa transaction. */
    public function releaseIfDue(Order $order): bool
    {
        if ($order->stock_reservation === null || $order->reservation_expires_at === null
            || $order->reservation_released_at !== null || $order->payment_status !== OrderPaymentStatus::Unpaid
            || ($order->reservation_expires_at->isFuture() && $order->status !== OrderStatus::Cancelled)) {
            return false;
        }

        // Quantités effectivement retirées à la création, cumulées entre variantes.
        $products = Product::whereIn('id', array_keys($order->stock_reservation))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        foreach ($order->stock_reservation as $productId => $quantity) {
            $product = $products->get($productId);
            if ($product !== null && $product->stock !== null) {
                $product->increment('stock', $quantity);
            } else {
                // Un article supprimé ou devenu non géré ne doit pas être recréé/modifié.
                Log::warning('order_stock_release_requires_review', ['order_id' => $order->id, 'product_id' => $productId]);
            }
        }
        $order->update(['reservation_released_at' => now()]);

        return true;
    }

    /** Appelé sous les verrous commande puis paiement, après vérification serveur. */
    public function paymentSucceeded(Order $order, Payment $payment): void
    {
        $this->releaseIfDue($order);
        $deliveryReview = ! $order->deliveryIsFinal();
        $review = $deliveryReview || $order->reservation_released_at !== null || $order->status === OrderStatus::Cancelled
            || $order->payment_review_required;
        $order->update([
            'payment_status' => OrderPaymentStatus::Paid,
            'payment_review_required' => $review,
            'confirmed_at' => $review ? $order->confirmed_at : ($order->confirmed_at ?? now()),
            'status' => ! $review && $order->status === OrderStatus::Pending ? OrderStatus::Confirmed : $order->status,
        ]);
        $payment->recordEvent($deliveryReview ? 'delivery_review_required' : ($review ? 'stock_review_required' : 'stock_consumed'), $payment->state);
    }
}
