<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

// Processus de test uniquement : aucune écriture possible hors d'une base temporaire nommée.
$directory = $argv[1] ?? '';
$userId = (int) ($argv[2] ?? 0);
$productId = (int) ($argv[3] ?? 0);
$tempRoot = realpath(sys_get_temp_dir());
$resolved = realpath($directory);
if ($tempRoot === false || $resolved === false || dirname($resolved) !== $tempRoot
    || preg_match('/\Atikmos-numbering-[a-f0-9]{16}\z/', basename($resolved)) !== 1
    || ! is_file($resolved.'/database.sqlite') || $userId < 1 || $productId < 1) {
    fwrite(STDERR, 'Dossier de test isolé invalide.');
    exit(2);
}
$root = dirname(__DIR__, 2);
foreach (['APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => $resolved.'/config.php',
    'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $resolved.'/database.sqlite', 'DB_URL' => '',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'MAIL_MAILER' => 'array', 'ORDER_NUMBER_SEQUENCE_FLOORS' => ''] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv($key.'='.$value);
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.connections.sqlite.busy_timeout' => 15000, 'app.timezone' => 'UTC',
    'logging.channels.single.path' => $resolved.'/worker-'.$userId.'.log']);
Carbon::setTestNow('2026-10-26 12:00:00 UTC');
Notification::fake();
Http::preventStrayRequests();

try {
    $user = User::findOrFail($userId);
    app(CartService::class)->add($productId);
    file_put_contents($resolved.'/ready-'.$userId, 'ready');
    $deadline = microtime(true) + 15;
    while (! is_file($resolved.'/go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Barrière de test expirée.');
        }
        usleep(10000);
    }
    $lockTime = null;
    DB::listen(function (QueryExecuted $query) use (&$lockTime): void {
        if ($lockTime === null && str_starts_with(strtolower($query->sql), 'insert into "order_number_sequences"')) {
            $lockTime = microtime(true);
            usleep(200000); // Maintient réellement le verrou pendant les autres tentatives.
        }
    });
    $started = microtime(true);
    $order = app(OrderService::class)->place($user, [
        'contact_name' => 'Client fictif', 'contact_phone' => 'Fictif', 'delivery_address' => 'Fictive',
    ]);
    $finished = microtime(true);
    Http::assertNothingSent();
    Notification::assertCount(1);
    echo json_encode(['number' => $order->number, 'id' => $order->id, 'user_id' => $userId,
        'product_id' => $productId, 'pid' => getmypid(), 'started' => $started,
        'finished' => $finished, 'locked_at' => $lockTime], JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    file_put_contents($resolved.'/error-'.$userId.'.txt', (string) $exception);
    fwrite(STDERR, 'Échec du processus fictif : '.get_class($exception));
    exit(1);
}
