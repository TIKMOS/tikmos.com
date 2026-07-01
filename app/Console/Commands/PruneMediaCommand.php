<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Media\ImageService;
use Illuminate\Console\Command;

class PruneMediaCommand extends Command
{
    protected $signature = 'media:prune';

    protected $description = 'Supprime les médias orphelins (dont le modèle parent n\'existe plus).';

    public function handle(ImageService $images): int
    {
        $count = $images->pruneOrphans();

        $this->info($count > 0 ? "{$count} média(s) orphelin(s) purgé(s)." : 'Aucun média orphelin.');

        return self::SUCCESS;
    }
}
