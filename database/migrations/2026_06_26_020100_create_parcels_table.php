<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcels', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('flight_id')->nullable()->constrained('flights')->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->nullable();
            $table->string('sender_phone')->nullable();
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->string('recipient_city')->nullable();
            $table->string('recipient_country')->nullable();
            $table->string('type'); // ParcelType
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 3)->default('XOF');
            $table->string('origin');       // ParcelOrigin
            $table->string('destination');  // ParcelOrigin
            $table->string('status')->default('registered'); // ParcelStatus
            $table->string('payment_status')->default('unpaid'); // PaymentStatus
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qr_token')->nullable()->unique();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('flight_id');
            $table->index('sender_id');
            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcels');
    }
};
