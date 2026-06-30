<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // ParcelType
            $table->decimal('weight_estimate', 8, 2)->nullable();
            $table->string('origin');       // ParcelOrigin
            $table->string('destination');  // ParcelOrigin
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new'); // RequestStatus
            $table->foreignId('converted_parcel_id')->nullable()->constrained('parcels')->nullOnDelete();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_requests');
    }
};
