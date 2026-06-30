<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table): void {
            $table->id();
            $table->string('direction'); // FlightDirection
            $table->date('departs_on');
            $table->date('arrives_on')->nullable();
            $table->decimal('capacity_kg', 8, 2)->nullable();
            $table->string('status')->default('scheduled'); // FlightStatus
            $table->boolean('is_public')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('departs_on');
            $table->index(['is_public', 'departs_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
