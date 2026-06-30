<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('courier_id')->nullable()->after('internal_notes')->constrained()->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('courier_id')->references('id')->on('couriers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['courier_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('courier_id');
        });
    }
};
