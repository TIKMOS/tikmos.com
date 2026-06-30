<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Models\Courier;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Settings\GeneralSettings;

class DeliveryService
{
    public function __construct(private CommissionCalculator $commission) {}

    /** Crée (ou récupère) la livraison d'une commande, en l'affectant éventuellement à un transporteur. */
    public function ensureForOrder(Order $order, ?Courier $courier = null): Delivery
    {
        $courier ??= $order->courier;

        $delivery = $order->delivery()->firstOrCreate(
            ['order_id' => $order->id],
            [
                'status' => DeliveryStatus::Pending,
                'pickup_location' => app(GeneralSettings::class)->addresses['cotonou'] ?? 'Agence SEZY',
                'dropoff_address' => $order->delivery_address,
                'dropoff_city' => $order->delivery_city,
            ]
        );

        if ($courier) {
            $this->assignCourier($delivery, $courier);
        }

        return $delivery;
    }

    public function assignCourier(Delivery $delivery, Courier $courier, ?User $livreur = null): void
    {
        $delivery->update([
            'courier_id' => $courier->id,
            'livreur_id' => $livreur ? $livreur->id : $delivery->livreur_id,
            'status' => $delivery->status === DeliveryStatus::Pending ? DeliveryStatus::Assigned : $delivery->status,
            'assigned_at' => $delivery->assigned_at ?? now(),
        ]);
        $delivery->order?->update(['courier_id' => $courier->id]);
    }

    public function assignLivreur(Delivery $delivery, User $livreur): void
    {
        $delivery->update(['livreur_id' => $livreur->id]);
    }

    /** Transition de statut (livreur). Calcule la commission et synchronise la commande à la livraison. */
    public function transition(Delivery $delivery, DeliveryStatus $to, ?string $failedReason = null): bool
    {
        if (! $delivery->status->canTransitionTo($to)) {
            return false;
        }

        $stamps = match ($to) {
            DeliveryStatus::PickedUp => ['picked_up_at' => now()],
            DeliveryStatus::EnRoute => ['en_route_at' => now()],
            DeliveryStatus::Delivered => ['delivered_at' => now()],
            DeliveryStatus::Failed => ['failed_reason' => $failedReason],
            default => [],
        };

        $delivery->update(['status' => $to, ...$stamps]);

        if ($to === DeliveryStatus::Delivered) {
            if ($delivery->courier) {
                $delivery->update(['commission_amount' => $this->commission->compute($delivery->courier, $delivery->order)]);
            }
            $delivery->order?->update(['status' => OrderStatus::Delivered, 'delivered_at' => now()]);
        }

        return true;
    }
}
