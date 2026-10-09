<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Composer\InstalledVersions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ManagedPackages
{
    public const PACKAGES = ['toast' => 'jeremykenedy/laravel-toast', 'laravel-roles' => 'jeremykenedy/laravel-roles', 'spatie' => 'spatie/laravel-permission'];

    public static function cache()
    {
        return Cache::store(config('laravelusers.settings.packages.cache'));
    }

    public function allowed(Model $actor): bool
    {
        return config('laravelusers.settings.enabled', false) && config('laravelusers.settings.packages.enabled', false)
            && UserAccess::allows('edit_settings', null, $actor)
            && Gate::forUser($actor)->allows(config('laravelusers.settings.packages.gate', 'manage-laravelusers-packages'));
    }

    public function installed(string $package): bool
    {
        return isset(self::PACKAGES[$package]) && InstalledVersions::isInstalled(self::PACKAGES[$package]);
    }

    public function listing(): array
    {
        return array_map(fn ($package) => $this->installed($package), array_combine(array_keys(self::PACKAGES), array_keys(self::PACKAGES)));
    }

    public function check(string $package, string $action): void
    {
        if (!isset(self::PACKAGES[$package]) || !in_array($action, ['install', 'remove'], true)) {
            $this->reject('Choose a supported package and operation.');
        }
        if ($action === 'install') {
            $this->checkInstallation($package);
        } else {
            $this->checkRemoval($package);
        }
    }

    private function checkInstallation(string $package): void
    {
        if ($this->installed($package)) {
            $this->reject('This package is already installed.');
        }
        if ($package !== 'toast' && ($this->installed('laravel-roles') || $this->installed('spatie'))) {
            $this->reject('A roles package is already installed. Remove it and complete the application changes before installing another.');
        }
        if ($package === 'toast' && (PHP_VERSION_ID < 80200 || version_compare(Application::VERSION, '10.0.0', '<'))) {
            $this->reject('Laravel Toast requires PHP 8.2 or newer and Laravel 10 or newer.');
        }
    }

    private function checkRemoval(string $package): void
    {
        if (!$this->installed($package)) {
            $this->reject('This package is not installed.');
        }
        if ($package !== 'toast') {
            $this->checkRoleRemoval($package);
        }
        $namespace = match ($package) {
            'toast'  => 'Jeremykenedy\\LaravelToast\\',
            'spatie' => 'Spatie\\Permission\\',
            default  => 'jeremykenedy\\LaravelRoles\\',
        };
        $this->checkApplicationReferences($namespace);
    }

    private function checkRoleRemoval(string $package): void
    {
        if (config('laravelusers.impersonation.enabled', false)) {
            $this->reject('Removal is blocked while user impersonation is enabled. Disable impersonation before removing the roles package.');
        }
        $trait = $package === 'spatie' ? 'Spatie\\Permission\\Traits\\HasRoles' : 'jeremykenedy\\LaravelRoles\\Traits\\HasRoleAndPermission';
        foreach (config('auth.providers', []) as $provider) {
            if ($this->modelUses($provider['model'] ?? null, $trait)) {
                $this->reject('Removal is blocked: an authentication model still uses this package. Remove its trait and application references first.');
            }
        }
        if ($this->modelUses(config('laravelusers.defaultUserModel'), $trait) || config('laravelusers.rolesEnabled')) {
            $this->reject('Removal is blocked: Laravel Users still uses the roles integration. Disable it and remove the model trait and middleware references first.');
        }
        foreach (config('laravelusers.access', []) as $rule) {
            if (($rule['mode'] ?? '') === 'restricted') {
                $this->reject('Removal is blocked: restricted access rules still depend on roles or permissions. Replace them with host authorization before removing the package.');
            }
        }
    }

    private function modelUses(mixed $model, string $trait): bool
    {
        return is_string($model) && class_exists($model) && in_array($trait, class_uses_recursive($model), true);
    }

    private function checkApplicationReferences(string $namespace): void
    {
        foreach (['app', 'routes', 'bootstrap', 'config'] as $directory) {
            $path = base_path($directory);
            if (!is_dir($path)) {
                continue;
            }
            foreach (File::allFiles($path) as $file) {
                if (!$this->shouldCheckFile($file)) {
                    continue;
                }
                $contents = $file->getContents();
                if (stripos($contents, $namespace) !== false || stripos($contents, str_replace('\\', '\\\\', $namespace)) !== false) {
                    $this->reject('Removal is blocked: application code still references this package in '.$file->getRelativePathname().'. Remove those references first.');
                }
            }
        }
    }

    private function shouldCheckFile(\SplFileInfo $file): bool
    {
        return $file->getExtension() === 'php'
            && !str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR)
            && !in_array($file->getFilename(), ['roles.php', 'permission.php', 'laraveltoast.php'], true)
            && !str_starts_with($file->getFilename(), 'laravelusers');
    }

    public function queueReady(): bool
    {
        $connection = config('laravelusers.settings.packages.connection') ?? config('queue.default');
        $driver = config('queue.connections.'.$connection.'.driver');

        return in_array($driver, ['database', 'redis', 'sqs', 'beanstalkd'], true)
            && ($driver === 'sqs' || (int) config('queue.connections.'.$connection.'.retry_after', 0) > 360)
            && !in_array(config('cache.stores.'.(config('laravelusers.settings.packages.cache') ?? config('cache.default')).'.driver'), ['array', 'null', 'octane'], true);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['package' => $message]);
    }
}
