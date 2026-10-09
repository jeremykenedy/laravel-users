<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use jeremykenedy\laravelusers\Console\ConsolePrompts;

class RolesSetup
{
    public const CHOICES = ['keep', 'none', 'laravel-roles', 'spatie'];

    private const PACKAGES = [
        'laravel-roles' => ['package' => 'jeremykenedy/laravel-roles', 'model' => 'jeremykenedy\\LaravelRoles\\Models\\Role', 'trait' => 'jeremykenedy\\LaravelRoles\\Traits\\HasRoleAndPermission'],
        'spatie'        => ['package' => 'spatie/laravel-permission', 'model' => 'Spatie\\Permission\\Models\\Role', 'trait' => 'Spatie\\Permission\\Traits\\HasRoles'],
    ];

    public function __construct(private readonly ComposerPackages $composer, private readonly Router $router, private readonly ManagedPackages $packages)
    {
    }

    public function configure(Command $command, bool $interactive): array|false
    {
        $choice = $command->option('roles');
        if ($choice === null && $interactive) {
            $command->line('Laravel Roles is the preferred package for new integrations. Keep preserves existing or custom integrations.');
            $choice = ConsolePrompts::select($command, 'Roles package (keep preserves existing or custom integrations)', array_combine(self::CHOICES, self::CHOICES), 'keep', $interactive);
        }
        if ($choice === null || $choice === 'keep') {
            return [];
        }
        if ($choice === 'none') {
            return ['rolesEnabled' => false];
        }

        return $this->enable($command, $choice, $interactive);
    }

    private function enable(Command $command, string $choice, bool $interactive): array|false
    {
        $package = self::PACKAGES[$choice];
        if (!class_exists($package['model'])) {
            return $this->install($command, $choice, $package, $interactive);
        }
        $roleModel = $this->readyRoleModel($command, $choice, $package);
        if ($roleModel === false) {
            return [];
        }
        $middleware = $this->middleware($command, $interactive);
        if ($middleware === false) {
            return false;
        }
        $command->info('Role selection enabled for '.$package['package'].'. Middleware: '.(is_array($middleware) ? implode(', ', $middleware) : $middleware));
        $command->line('Keep role tables on the user connection. Existing roles and permissions are preserved; no migrations or seeds are run.');

        return ['rolesEnabled' => true, 'roleModel' => $roleModel, 'rolesMiddlwareEnabled' => true, 'rolesMiddlware' => $middleware];
    }

    private function install(Command $command, string $choice, array $package, bool $interactive): array|false
    {
        $other = $choice === 'spatie' ? 'laravel-roles' : 'spatie';
        if ($this->packages->installed($other)) {
            $command->error('A roles package is already installed. Complete its removal and application changes before installing another. Existing role settings are unchanged.');

            return false;
        }
        $install = $command->option('install-roles');
        if (!$install && $interactive) {
            $install = ConsolePrompts::confirm($command, 'Install '.$package['package'].' with Composer now?', $interactive, false);
        }
        if ($install && !$this->composer->install($package['package'], fn ($text) => $command->getOutput()->write($text))) {
            $command->error('Roles installation failed. Laravel Users configuration was not changed. Review Composer output and the host lock file.');

            return false;
        }
        $command->line('Run: composer require '.$package['package']);
        $this->instructions($command, $choice, $package['trait']);
        $command->warn('Complete the host model and database setup, then run php artisan laravelusers:update --roles='.$choice.'. Existing role settings are unchanged.');

        return [];
    }

    private function readyRoleModel(Command $command, string $choice, array $package): string|false
    {
        $userModel = config('laravelusers.defaultUserModel', 'App\\Models\\User');
        if (!class_exists($userModel) || !in_array($package['trait'], class_uses_recursive($userModel), true)) {
            $this->instructions($command, $choice, $package['trait']);
            $command->warn('Add the trait to '.$userModel.' and run this command again. Existing role settings are unchanged.');

            return false;
        }
        $roleModel = config($choice === 'spatie' ? 'permission.models.role' : 'roles.models.role', $package['model']);
        $user = new $userModel();
        $role = new $roleModel();
        $schema = $user->getConnection()->getSchemaBuilder();
        if (!$schema->hasTable($role->getTable()) || !$schema->hasTable($user->roles()->getTable())) {
            $this->instructions($command, $choice, $package['trait']);
            $command->warn('Role tables are missing from the user connection. Complete migrations and run this command again. Existing role settings are unchanged.');

            return false;
        }

        return $roleModel;
    }

    private function middleware(Command $command, bool $interactive): array|string|false
    {
        $middleware = $command->option('role-middleware');
        if ($middleware === null && $interactive) {
            $default = config('laravelusers.rolesMiddlware', 'role:admin');
            $middleware = ConsolePrompts::text($command, 'Role middleware (register the alias in your host application)', is_array($default) ? implode(';', $default) : $default, $interactive);
        }
        $middleware = $middleware ?? config('laravelusers.rolesMiddlware', 'role:admin');
        if (is_string($middleware) && str_contains($middleware, ';')) {
            $middleware = array_values(array_filter(array_map('trim', explode(';', $middleware))));
        }
        if (!$middleware) {
            $command->error('Choose management middleware before enabling a roles package. Existing role settings are unchanged.');

            return false;
        }

        return $this->registeredMiddleware($command, $middleware);
    }

    private function registeredMiddleware(Command $command, array|string $middleware): array|string|false
    {
        foreach ((array) $middleware as $entry) {
            $alias = explode(':', $entry, 2)[0];
            if (!isset($this->router->getMiddleware()[$alias]) && !isset($this->router->getMiddlewareGroups()[$alias]) && !class_exists($alias)) {
                $command->error('Register the '.$alias.' middleware alias before enabling roles. Existing role settings are unchanged.');

                return false;
            }
        }

        return $middleware;
    }

    private function instructions(Command $command, string $choice, string $trait): void
    {
        $command->line('Add this trait inside your configured user model: use \\'.$trait.';');
        if ($choice === 'spatie') {
            $command->line('Publish: php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"');
            $command->line('Register the role and permission middleware aliases for your installed Spatie version.');
        } else {
            $command->line('Publish: php artisan vendor:publish --tag=laravelroles-migrations');
        }
        $command->line('Review the published migrations, then run php artisan migrate. Assign an administrator role before enabling its middleware.');
    }
}
