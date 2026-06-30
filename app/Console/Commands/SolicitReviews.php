<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ReviewSolicitationService;
use Illuminate\Console\Command;

class SolicitReviews extends Command
{
    protected $signature = 'reviews:solicit';

    protected $description = 'Sollicite des avis pour les colis livrés éligibles (délai + cooldown).';

    public function handle(ReviewSolicitationService $service): int
    {
        $count = $service->solicitForDeliveredParcels();

        $this->info("Sollicitations d'avis envoyées : {$count}");

        return self::SUCCESS;
    }
}
