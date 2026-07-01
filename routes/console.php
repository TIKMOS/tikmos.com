<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
| Planificateur — un seul cron pilote tout :
|   * * * * * cd /chemin/projet && php artisan schedule:run >> /dev/null 2>&1
|
| ⚠️ Hébergement mutualisé où exec()/proc_open sont désactivés : on N'UTILISE PAS
| Schedule::command() ni ->runInBackground() (ils lancent un sous-processus via
| proc_open). On exécute chaque commande EN PROCESSUS avec Artisan::call().
|
| Recommandé : QUEUE_CONNECTION=sync → les e-mails (dont les codes 2FA) partent
| immédiatement, aucun worker requis. Le drain ci-dessous reste utile si la file
| est configurée en base (QUEUE_CONNECTION=database).
*/

// File d'attente — drainée en processus, sans proc_open (no-op si QUEUE_CONNECTION=sync).
Schedule::call(fn () => Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--max-time' => 50,
    '--tries' => 3,
]))->everyMinute()->name('tk-queue-work')->withoutOverlapping();

// Purge des modèles « prunable » (appareils de confiance 2FA expirés).
Schedule::call(fn () => Artisan::call('model:prune'))
    ->daily()->name('tk-model-prune')->withoutOverlapping();

// Purge des médias orphelins (fichiers sans modèle parent).
Schedule::call(fn () => Artisan::call('media:prune'))
    ->weekly()->name('tk-media-prune')->withoutOverlapping();

// Rétention du journal d'activité et des connexions.
Schedule::call(fn () => Artisan::call('audit:prune'))
    ->weekly()->name('tk-audit-prune')->withoutOverlapping();
