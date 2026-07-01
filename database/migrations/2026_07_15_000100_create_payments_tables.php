<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();

            // Rattachement polymorphe (commande, devis… portable).
            $table->morphs('payable');

            $table->string('provider');              // 'kkiapay' | 'cod' | …
            $table->string('reference')->unique();   // notre référence idempotente
            $table->string('provider_reference')->nullable()->index();
            $table->unsignedBigInteger('amount');    // XOF entier
            $table->string('currency', 3)->default('XOF');
            $table->string('state')->default('pending');
            $table->string('method')->nullable();    // momo | card | cash | …
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('type');                  // initialized | verified | webhook | refunded …
            $table->string('state')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
    }
};
