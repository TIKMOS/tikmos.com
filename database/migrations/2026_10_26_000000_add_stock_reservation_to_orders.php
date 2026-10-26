<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            // NULL distingue explicitement les commandes historiques, sans les réécrire.
            $table->timestamp('reservation_expires_at')->nullable()->index();
            $table->timestamp('reservation_released_at')->nullable();
            $table->json('stock_reservation')->nullable();
            $table->boolean('payment_review_required')->default(false);
            $table->uuid('checkout_token')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique(['checkout_token']);
            $table->dropIndex(['reservation_expires_at']);
            $table->dropColumn(['reservation_expires_at', 'reservation_released_at', 'stock_reservation', 'payment_review_required', 'checkout_token']);
        });
    }
};
