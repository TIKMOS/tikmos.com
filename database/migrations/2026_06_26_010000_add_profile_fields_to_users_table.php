<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
            $table->string('city')->nullable()->after('phone');
            $table->string('country')->nullable()->after('city');
            $table->string('locale', 5)->default('fr')->after('country');
            $table->string('status')->default('active')->after('locale'); // active | pending | suspended
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index('phone');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropSoftDeletes();
            $table->dropColumn(['phone', 'city', 'country', 'locale', 'status', 'two_factor_confirmed_at']);
        });
    }
};
