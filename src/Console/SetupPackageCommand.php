<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\ManagedPackages;

class SetupPackageCommand extends Command
{
    protected $signature = 'laravelusers:setup-package {package : toast, laravel-roles, or spatie} {--framework= : bootstrap4, bootstrap5, or tailwind} {--migrate : Run only the selected package migrations}';

    protected $description = 'Publish missing optional package configuration and set up its database';

    public function handle(ManagedPackages $packages): int
    {
        $package = $this->argument('package');
        $framework = $this->option('framework') ?? Frontend::framework();
        if (!$packages->installed($package) || !in_array($framework, Frontend::FRAMEWORKS, true)) {
            $this->error('Select an installed optional package and a supported CSS framework.');

            return self::FAILURE;
        }
        if ($this->laravel->configurationIsCached()) {
            $this->error('Clear the configuration cache before package setup, then rebuild it afterwards.');

            return self::FAILURE;
        }
        if ($package === 'toast') {
            if (is_file(config_path('toast.php'))) {
                $this->info('Existing Laravel Toast configuration was preserved.');

                return self::SUCCESS;
            }

            return $this->call('toast:install', ['--css' => $framework, '--frontend' => 'blade', '--no-interaction' => true]);
        }
        $provider = $package === 'spatie' ? 'Spatie\\Permission\\PermissionServiceProvider' : 'jeremykenedy\\LaravelRoles\\RolesServiceProvider';
        if ($this->call('vendor:publish', ['--provider' => $provider, '--tag' => $package === 'spatie' ? 'permission-config' : 'laravelroles-config', '--no-interaction' => true]) !== 0) {
            return self::FAILURE;
        }
        $migrations = array_filter(ServiceProvider::pathsToPublish($provider), fn ($target) => str_starts_with($target, database_path('migrations')));
        if ($this->option('migrate')) {
            foreach ($migrations as $source => $target) {
                $path = $source;
                if (str_ends_with($source, '.stub')) {
                    if ($this->call('vendor:publish', ['--provider' => $provider, '--tag' => 'permission-migrations', '--no-interaction' => true]) !== 0) {
                        return self::FAILURE;
                    }
                    $path = $target;
                }
                if ($this->call('migrate', ['--path' => $path, '--realpath' => true, '--force' => true, '--no-interaction' => true]) !== 0) {
                    return self::FAILURE;
                }
            }
        }
        $this->info('Missing configuration published. Existing package settings and application migrations were preserved.');
        $this->line('Add the selected package trait to your configured user model, register its middleware and assign an administrator role before enabling the integration.');
        $this->line('Then run: php artisan laravelusers:update --roles='.$package);

        return self::SUCCESS;
    }
}
