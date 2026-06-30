<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CommissionType;
use App\Models\Courier;
use App\Models\Order;

class CommissionCalculator
{
    /** Commission (FCFA entier) due au transporteur pour une commande livrée. */
    public function compute(Courier $courier, Order $order): int
    {
        return match ($courier->commission_type) {
            CommissionType::Percent => (int) round($order->total * $courier->commission_value / 100),
            CommissionType::Flat, CommissionType::PerDelivery => $courier->commission_value,
            CommissionType::None => 0,
        };
    }
}
