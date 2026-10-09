<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PackageRequirements
{
    public function __construct(private readonly Application $app, private readonly Filesystem $files, private readonly Kernel $console, private readonly ComposerPackages $composer)
    {
    }

    public function configure(): void
    {
        if ($this->app->configurationIsCached()) {
            throw ValidationException::withMessages(['package' => 'Clear the configuration cache before setting up package requirements, then rebuild it afterwards.']);
        }
        $path = config_path('laravelusers-packages.php');
        $this->files->ensureDirectoryExists(config_path());
        if (!$this->files->exists($path)) {
            $this->files->put($path, <<<'PHP'
<?php

return [
    'connection' => env('LARAVEL_USERS_SETTINGS_PACKAGES_CONNECTION', 'laravelusers-packages'),
    'cache' => env('LARAVEL_USERS_SETTINGS_PACKAGES_CACHE', 'laravelusers-packages'),
    'database' => env('LARAVEL_USERS_SETTINGS_PACKAGES_DATABASE', null),
];

PHP);
        }
        config(['laravelusers-packages' => require $path]);
        self::load();
        $this->files->ensureDirectoryExists(storage_path('framework/cache/laravelusers-packages'));
        $status = $this->console->call('migrate', ['--path' => dirname(__DIR__).'/database/package-jobs', '--realpath' => true, '--force' => true, '--no-interaction' => true]);
        if ($status !== 0) {
            throw ValidationException::withMessages(['package' => 'Queue storage setup failed. Review the database connection and migration output before retrying.']);
        }
        $lock = ManagedPackages::cache()->lock('laravelusers.requirements-check', 10);
        if (!$lock->get()) {
            throw ValidationException::withMessages(['package' => 'The package cache lock could not be acquired. Check the cache configuration before retrying.']);
        }
        $lock->release();
    }

    public function verify(ManagedPackages $packages): bool
    {
        return $this->status($packages)['queue_ready'];
    }

    public function status(ManagedPackages $packages): array
    {
        try {
            if (!$packages->queueReady()) {
                return $this->result(false, 'laravelusers::ui.package_requirements_not_verified');
            }
            $name = config('laravelusers.settings.packages.connection') ?? config('queue.default');
            $queue = config('queue.connections.'.$name, []);
            if (($queue['driver'] ?? null) === 'database' && !Schema::connection($queue['connection'] ?? null)->hasTable($queue['table'] ?? 'jobs')) {
                return $this->result(false, 'laravelusers::ui.package_requirements_not_verified');
            }
            $lock = ManagedPackages::cache()->lock('laravelusers.requirements-verify', 10);
            if (!$lock->get()) {
                return $this->result(false, 'laravelusers::ui.package_requirements_not_verified');
            }
            $lock->release();
            if ($failure = $this->composer->readiness()) {
                return $this->result(false, $failure);
            }
            if ($failure = PackageWorker::failure()) {
                return $this->result(false, $failure);
            }
            if (!PackageWorker::verified()) {
                PackageWorker::probe();

                return $this->result(false, 'laravelusers::ui.package_worker_verifying', 'checking');
            }

            return $this->result(true, 'laravelusers::ui.package_requirements_verified');
        } catch (\Throwable) {
            return $this->result(false, 'laravelusers::ui.package_requirements_not_verified');
        }
    }

    private function result(bool $ready, string $message, string $status = 'not_ready'): array
    {
        return [
            'status'         => $ready ? 'completed' : $status,
            'queue_ready'    => $ready,
            'message'        => trans($message),
            'worker_command' => 'php artisan queue:work '.(config('laravelusers.settings.packages.connection') ?? config('queue.default')).' --queue='.config('laravelusers.settings.packages.queue', 'default').' --timeout=360',
        ];
    }

    public static function load(): void
    {
        if (!config('laravelusers-packages')) {
            return;
        }
        config([
            'queue.connections.laravelusers-packages'   => ['driver' => 'database', 'connection' => config('laravelusers-packages.database'), 'table' => 'laravelusers_package_jobs', 'queue' => config('laravelusers.settings.packages.queue', 'default'), 'retry_after' => 600],
            'cache.stores.laravelusers-packages'        => ['driver' => 'file', 'path' => storage_path('framework/cache/laravelusers-packages'), 'lock_path' => storage_path('framework/cache/laravelusers-packages/locks')],
            'laravelusers.settings.packages.connection' => config('laravelusers-packages.connection'),
            'laravelusers.settings.packages.cache'      => config('laravelusers-packages.cache'),
        ]);
    }
}
