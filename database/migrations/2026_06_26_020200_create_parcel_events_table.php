<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcel_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parcel_id')->constrained('parcels')->cascadeOnDelete();
            $table->string('status'); // ParcelStatus (snapshot de l'étape)
            $table->string('comment')->nullable();
            $table->string('location')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index('parcel_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcel_events');
    }
};
