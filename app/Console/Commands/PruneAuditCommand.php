<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LoginLog;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class PruneAuditCommand extends Command
{
    protected $signature = 'audit:prune {--days=365 : Rétention en jours}';

    protected $description = 'Purge le journal d\'activité et les logs de connexion au-delà de la rétention.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $activities = Activity::where('created_at', '<', $cutoff)->delete();
        $logins = LoginLog::where('logged_in_at', '<', $cutoff)->delete();

        $this->info("Purge audit : {$activities} activité(s), {$logins} connexion(s) supprimée(s) (> {$days} j).");

        return self::SUCCESS;
    }
}
