<?php

declare(strict_types=1);

use App\Support\OrderNumberAllocator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Valider d'abord : le DDL MySQL n'est pas annulé par un rollback de données.
        $counters = app(OrderNumberAllocator::class)->installationCounters();
        Schema::create('order_number_sequences', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        foreach ($counters as $year => $lastNumber) {
            DB::table('order_number_sequences')->insert(['year' => $year, 'last_number' => $lastNumber]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_number_sequences');
    }
};
