<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->json('zones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('commission_type')->default('per_delivery');
            $table->unsignedInteger('commission_value')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
