<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\OrderNumberAllocator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Process\Process;

it('exige une transaction pour verrouiller ou attribuer un numéro', function (): void {
    expect(fn () => app(OrderNumberAllocator::class)->lockCurrentYear())->toThrow(LogicException::class)
        ->and(fn () => app(OrderNumberAllocator::class)->nextForYear(2026))->toThrow(LogicException::class);
});

it('crée quatre commandes simultanées avec des processus clients et produits distincts', function (bool $existingCounter): void {
    Http::preventStrayRequests();
    Notification::fake();
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tikmos-numbering-'.bin2hex(random_bytes(8));
    mkdir($directory);
    touch($directory.'/database.sqlite');
    $previousDefault = config('database.default');
    config(['database.connections.numbering_concurrency' => [
        'driver' => 'sqlite', 'database' => $directory.'/database.sqlite', 'prefix' => '',
        'foreign_key_constraints' => true, 'busy_timeout' => 15000, 'transaction_mode' => 'DEFERRED',
    ], 'database.default' => 'numbering_concurrency', 'orders.number_sequence_floors' => null]);
    $processes = [];

    try {
        expect(Artisan::call('migrate', ['--database' => 'numbering_concurrency', '--force' => true]))->toBe(0);
        $this->seed(RolePermissionSeeder::class);
        if ($existingCounter) {
            DB::table('order_number_sequences')->insert(['year' => 2026, 'last_number' => 1000]);
        }
        $productIds = [];
        $userIds = [];
        for ($i = 0; $i < 4; $i++) {
            $user = User::factory()->create(['name' => 'Client fictif '.$i, 'status' => 'active']);
            $user->assignRole(Role::Client->value);
            $product = Product::create(['name' => 'Article fictif '.$i, 'slug' => 'simultane-'.$i,
                'price' => 1000, 'stock' => 2, 'is_active' => true]);
            $userIds[] = $user->id;
            $productIds[] = $product->id;
            $process = new Process([PHP_BINARY, base_path('tests/Support/order-number-worker.php'), $directory, (string) $user->id, (string) $product->id]);
            $process->setTimeout(35);
            $process->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 20;
        while (count(glob($directory.'/ready-*')) !== 4) {
            foreach ($processes as $process) {
                if (! $process->isRunning()) {
                    throw new RuntimeException('Processus non prêt : '.$process->getErrorOutput());
                }
            }
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Les quatre processus ne sont pas devenus prêts.');
            }
            usleep(10000);
        }
        file_put_contents($directory.'/go', 'go');
        $results = [];
        foreach ($processes as $process) {
            expect($process->wait())->toBe(0, $process->getErrorOutput());
            $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        }
        $numbers = array_column($results, 'number');
        sort($numbers);
        $base = $existingCounter ? 1000 : 0;
        expect($numbers)->toBe(array_map(fn (int $i): string => sprintf('TKM-CMD-2026-%05d', $base + $i), [1, 2, 3, 4]))
            ->and(count(array_unique(array_column($results, 'pid'))))->toBe(4)
            ->and(count(array_unique(array_column($results, 'user_id'))))->toBe(4)
            ->and(count(array_unique(array_column($results, 'product_id'))))->toBe(4)
            ->and(max(array_column($results, 'started')))->toBeLessThan(min(array_column($results, 'finished')))
            ->and(array_filter(array_column($results, 'locked_at'), fn ($time): bool => $time !== null))->toHaveCount(4)
            ->and(Order::count())->toBe(4)
            ->and(DB::table('order_items')->count())->toBe(4)
            ->and(Product::whereIn('id', $productIds)->pluck('stock')->all())->toBe([1, 1, 1, 1])
            ->and(DB::table('order_number_sequences')->where('year', 2026)->value('last_number'))->toBe($base + 4);
    } finally {
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
        DB::purge('numbering_concurrency');
        config(['database.default' => $previousDefault]);
        // Suppression non récursive des seuls fichiers de ce dossier temporaire vérifié.
        if (realpath(dirname($directory)) === realpath(sys_get_temp_dir())
            && preg_match('/\Atikmos-numbering-[a-f0-9]{16}\z/', basename($directory)) === 1) {
            foreach (glob($directory.'/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory);
        }
    }
})->with(['première allocation annuelle' => false, 'compteur annuel déjà présent' => true]);
