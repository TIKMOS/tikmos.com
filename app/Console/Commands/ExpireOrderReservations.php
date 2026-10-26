<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderReservationService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ExpireOrderReservations extends Command
{
    protected $signature = 'orders:expire-reservations';

    protected $description = 'Libère une seule fois le stock des réservations en ligne échues';

    public function handle(OrderReservationService $reservations): int
    {
        $released = 0;
        Order::whereNotNull('reservation_expires_at')->whereNull('reservation_released_at')
            ->where('payment_status', 'unpaid')
            ->where(fn (Builder $query) => $query->where('reservation_expires_at', '<=', now())
                ->orWhere('status', OrderStatus::Cancelled->value))
            ->chunkById(100, function ($orders) use ($reservations, &$released): void {
                foreach ($orders as $order) {
                    $released += (int) $reservations->expire($order);
                }
            });
        $this->info($released.' réservation(s) libérée(s).');

        return self::SUCCESS;
    }
}
