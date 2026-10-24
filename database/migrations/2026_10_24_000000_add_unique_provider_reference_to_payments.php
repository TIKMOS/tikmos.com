<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicates = DB::table('payments')
            ->select('provider', 'provider_reference')
            ->whereNotNull('provider_reference')
            ->groupBy('provider', 'provider_reference')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Migration des paiements interrompue : doublons fournisseur/référence externe. '
                .'Réconcilier manuellement les paiements historiques avant de relancer ; aucune donnée n’a été modifiée.'
            );
        }

        Schema::table('payments', function (Blueprint $table): void {
            $table->unique(['provider', 'provider_reference'], 'payments_provider_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique('payments_provider_reference_unique');
        });
    }
};
