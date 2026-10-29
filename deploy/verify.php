<?php

declare(strict_types=1);
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Migrations\Migrator;

// Standalone control-plane helper: no Composer or Laravel bootstrap except runtime.
class DeploymentRefusal extends RuntimeException {}

function deployRequire(bool $condition, string $message): void
{
    if (! $condition) {
        throw new DeploymentRefusal($message);
    }
}

/** @return array<string, mixed> */
function deployJson(string $json): array
{
    $value = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    deployRequire(is_array($value), 'Invalid JSON response.');

    return $value;
}

/** @return array<string, mixed> */
function deployApi(string $path, int $timeout): array
{
    $handle = curl_init('https://api.github.com/repos/'.$path);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_USERAGENT => 'Tikmos-Cpanel-Deployment/1',
        CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json', 'X-GitHub-Api-Version: 2022-11-28'],
    ]);
    $response = curl_exec($handle);
    $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);
    deployRequire(is_string($response) && $status === 200, 'GitHub API unavailable, rate limited or validation missing. Keep current release.');
    deployRequire(strlen($response) < 4 * 1024 * 1024, 'GitHub response too large.');

    return deployJson($response);
}

/** @param array<string, mixed> $runs
 * @return array<string, mixed>
 */
function deployRun(array $runs, string $repository, string $branch, string $sha): array
{
    // No status=success filter: a latest pending/failed attempt must block.
    $run = $runs['workflow_runs'][0] ?? [];
    deployRequire(($run['head_sha'] ?? null) === $sha && ($run['head_branch'] ?? null) === $branch, 'No CI run for exact main SHA.');
    deployRequire(($run['event'] ?? null) === 'push' && ($run['path'] ?? null) === '.github/workflows/ci.yml', 'Unexpected workflow or event.');
    deployRequire(($run['head_repository']['full_name'] ?? null) === $repository, 'Unexpected workflow repository.');
    deployRequire(($run['status'] ?? null) === 'completed' && ($run['conclusion'] ?? null) === 'success', 'CI not completely successful.');
    deployRequire(is_int($run['id'] ?? null) && is_int($run['run_attempt'] ?? null), 'Invalid CI identity.');

    return $run;
}

/** @param array<string, mixed> $jobs */
function deployJobs(array $jobs): void
{
    deployRequire(($jobs['total_count'] ?? 0) === 2 && count($jobs['jobs'] ?? []) === 2, 'Incomplete or unexpected CI jobs.');
    $names = [];
    foreach ($jobs['jobs'] as $job) {
        deployRequire(($job['status'] ?? null) === 'completed' && ($job['conclusion'] ?? null) === 'success', 'A required CI job did not succeed.');
        $names[] = $job['name'];
    }
    sort($names);
    deployRequire($names === ['publish', 'validate'], 'Required CI jobs missing.');
}

/** @param array<string, mixed> $release
 * @return array{digest: string, url: string, size: int}
 */
function deployAsset(array $release, string $repository, string $tag, string $sha): array
{
    deployRequire(($release['tag_name'] ?? null) === $tag && ($release['target_commitish'] ?? null) === $sha && ($release['draft'] ?? true) === false, 'Release identity differs from validated SHA.');
    $assets = array_values(array_filter($release['assets'] ?? [], fn (array $asset): bool => ($asset['name'] ?? '') === 'tikmos.tar.gz'));
    deployRequire(count($assets) === 1, 'Expected one public production archive.');
    $asset = $assets[0];
    $digest = $asset['digest'] ?? '';
    $url = 'https://github.com/'.$repository.'/releases/download/'.$tag.'/tikmos.tar.gz';
    deployRequire(($asset['state'] ?? '') === 'uploaded' && ($asset['browser_download_url'] ?? '') === $url, 'Unexpected asset location or state.');
    deployRequire(is_string($digest) && preg_match('/^sha256:[a-f0-9]{64}$/D', $digest) === 1 && is_int($asset['size'] ?? null) && $asset['size'] > 0, 'Asset digest or size missing.');

    return ['digest' => substr($digest, 7), 'url' => $url, 'size' => $asset['size']];
}

function deployArchive(string $path, string $digest, int $limit, string $sha, int $run, int $attempt): void
{
    deployRequire(hash_equals($digest, hash_file('sha256', $path)), 'Archive SHA256 mismatch.');
    $archive = new PharData($path);
    $prefix = 'phar://'.str_replace('\\', '/', realpath($path)).'/';
    $total = 0;
    $count = 0;
    foreach (new RecursiveIteratorIterator($archive, RecursiveIteratorIterator::SELF_FIRST) as $file) {
        $name = substr(str_replace('\\', '/', $file->getPathname()), strlen($prefix));
        if ($file->isDir() && in_array($name, ['app', 'bootstrap', 'config', 'database', 'lang', 'public', 'resources', 'routes', 'vendor'], true)) {
            continue;
        }
        deployRequire(preg_match('~^(?:app|bootstrap|config|database|lang|public|resources|routes|vendor)/[a-zA-Z0-9_./@+ -]+$|^(?:artisan|composer\.json|composer\.lock|\.deploy-release\.json)$~D', $name) === 1, 'Unexpected archive member.');
        deployRequire(! str_contains($name, '..') && ! $file->isLink() && ($file->isDir() || $file->isFile()), 'Unsafe archive member.');
        deployRequire(! preg_match('~(?:^|/)(?:\.env[^/]*|auth\.json|\.git|hot)(?:/|$)|^public/storage(?:/|$)|^bootstrap/cache/.+~', $name), 'Sensitive or generated state in archive.');
        $total += $file->getSize();
        deployRequire($total <= $limit && ++$count <= 100000, 'Expanded archive limit exceeded.');
    }
    $meta = deployJson($archive['.deploy-release.json']->getContent());
    deployRequire(($meta['sha'] ?? null) === $sha && ($meta['run_id'] ?? null) === $run && ($meta['attempt'] ?? null) === $attempt, 'Archive metadata differs from CI proof.');
    deployRequire(preg_match('/^[a-f0-9]{64}$/D', $meta['migrations'] ?? '') === 1, 'Migration fingerprint absent.');
    foreach (['artisan', 'vendor/autoload.php', 'composer.lock', 'public/index.php', 'public/build/manifest.json', 'public/deployment-version.txt'] as $required) {
        deployRequire(isset($archive[$required]), 'Required release file missing.');
    }
    deployRequire(trim($archive['public/deployment-version.txt']->getContent()) === $sha, 'Public version mismatch.');
}

function deployMigrationFingerprint(string $root): string
{
    $context = hash_init('sha256');
    foreach (['database/migrations', 'database/settings'] as $directory) {
        $files = glob($root.'/'.$directory.'/*.php');
        sort($files, SORT_STRING);
        foreach ($files as $file) {
            hash_update($context, $directory.'/'.basename($file)."\0".file_get_contents($file));
        }
    }

    return hash_final($context);
}

function deployPendingMigrations(Migrator $migrator, string $migrationPath): bool
{
    $repository = $migrator->getRepository();
    $files = $migrator->getMigrationFiles([...$migrator->paths(), $migrationPath]);

    return ! $repository->repositoryExists() || array_diff(array_keys($files), $repository->getRan()) !== [];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        $command = $argv[1] ?? '';
        if ($command === 'github') {
            [, , $repository, $branch, $sha, $timeout] = $argv;
            deployRequire(preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D', $repository) === 1 && $branch === 'main' && preg_match('/^[a-f0-9]{40}$/D', $sha) === 1, 'Invalid deployment identity.');
            $repo = deployApi($repository, (int) $timeout);
            deployRequire(($repo['private'] ?? true) === false && ($repo['full_name'] ?? '') === $repository && ($repo['default_branch'] ?? '') === $branch, 'Expected public repository and main default branch.');
            $runs = deployApi($repository.'/actions/workflows/ci.yml/runs?branch=main&event=push&head_sha='.$sha.'&per_page=1', (int) $timeout);
            $run = deployRun($runs, $repository, $branch, $sha);
            deployJobs(deployApi($repository.'/actions/runs/'.$run['id'].'/attempts/'.$run['run_attempt'].'/jobs?per_page=100', (int) $timeout));
            $tag = 'deploy-'.$sha.'-'.$run['id'].'-'.$run['run_attempt'];
            $asset = deployAsset(deployApi($repository.'/releases/tags/'.$tag, (int) $timeout), $repository, $tag, $sha);
            echo implode("\t", [$sha, $run['id'], $run['run_attempt'], $asset['digest'], $asset['url'], $asset['size']]).PHP_EOL;
        } elseif ($command === 'archive') {
            deployArchive($argv[2], $argv[3], (int) $argv[4], $argv[5], (int) $argv[6], (int) $argv[7]);
        } elseif ($command === 'metadata') {
            $meta = deployJson(file_get_contents($argv[2]));
            $key = $argv[3];
            deployRequire(in_array($key, ['sha', 'migrations'], true) && preg_match($key === 'sha' ? '/^[a-f0-9]{40}$/D' : '/^[a-f0-9]{64}$/D', $meta[$key] ?? '') === 1, 'Invalid release metadata.');
            echo $meta[$key].PHP_EOL;
        } elseif ($command === 'baseline') {
            deployRequire(is_dir($argv[2]) && preg_match('/^[a-f0-9]{40}$/D', $argv[3]) === 1, 'Invalid existing installation.');
            // Read-only metadata for an operator-created snapshot of existing production.
            // This never authorizes a candidate deployment (which always requires CI).
            echo json_encode(['sha' => $argv[3], 'migrations' => deployMigrationFingerprint($argv[2]), 'manual_baseline' => true], JSON_THROW_ON_ERROR).PHP_EOL;
        } elseif (in_array($command, ['runtime', 'pending', 'ready'], true)) {
            require $argv[2].'/vendor/autoload.php';
            $app = require $argv[2].'/bootstrap/app.php';
            $app->make(Kernel::class)->bootstrap();
            deployRequire($app->environment('production') && ! $app['config']->get('app.debug') && (bool) $app['config']->get('app.key'), 'Require production environment, debug off and existing APP_KEY.');
            deployRequire($app['config']->get('app.maintenance.driver') === 'file', 'Only file maintenance driver is supported.');
            deployRequire(in_array($app['config']->get('queue.default'), ['sync', 'database'], true), 'Long-lived workers require a separate deployment policy.');
            deployRequire(in_array($app['config']->get('database.default'), ['mysql', 'mariadb'], true), 'Shared SQLite database paths require a separate policy.');
            $app['db']->connection()->select('SELECT 1');
            if ($command !== 'runtime') {
                $pending = deployPendingMigrations($app['migrator'], $app->databasePath('migrations'));
                if ($command === 'pending') {
                    echo $pending ? "yes\n" : "no\n";
                } else {
                    deployRequire(! $pending, 'Unapplied database migrations remain; refuse switch.');
                }
            }
        } else {
            throw new RuntimeException('Unknown verification command.');
        }
    } catch (Throwable $exception) {
        // Do not leak paths, SQL, credentials, headers, or server exception details.
        $reason = $exception instanceof DeploymentRefusal ? $exception->getMessage() : 'See documentation and private operator diagnostics.';
        fwrite(STDERR, 'Deployment verification refused ('.$command.'). '.$reason.PHP_EOL);
        exit(1);
    }
}
