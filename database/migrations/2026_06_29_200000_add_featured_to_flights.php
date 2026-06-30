<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('is_public');
            $table->timestamp('cutoff_at')->nullable()->after('arrives_on');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table): void {
            $table->dropColumn(['is_featured', 'cutoff_at']);
        });
    }
};
