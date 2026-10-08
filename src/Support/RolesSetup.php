<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;

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
            $choice = $command->choice('Roles package (keep preserves existing or custom integrations)', self::CHOICES, 'keep');
        }
        if ($choice === null || $choice === 'keep') {
            return [];
        }
        if ($choice === 'none') {
            return ['rolesEnabled' => false];
        }
        $package = self::PACKAGES[$choice];
        if (!class_exists($package['model'])) {
            $other = $choice === 'spatie' ? 'laravel-roles' : 'spatie';
            if ($this->packages->installed($other)) {
                $command->error('A roles package is already installed. Complete its removal and application changes before installing another. Existing role settings are unchanged.');

                return false;
            }
            $install = $command->option('install-roles');
            if (!$install && $interactive) {
                $install = $command->confirm('Install '.$package['package'].' with Composer now?', false);
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
        $userModel = config('laravelusers.defaultUserModel', 'App\\Models\\User');
        if (!class_exists($userModel) || !in_array($package['trait'], class_uses_recursive($userModel), true)) {
            $this->instructions($command, $choice, $package['trait']);
            $command->warn('Add the trait to '.$userModel.' and run this command again. Existing role settings are unchanged.');

            return [];
        }
        $roleModel = config($choice === 'spatie' ? 'permission.models.role' : 'roles.models.role', $package['model']);
        $user = new $userModel();
        $role = new $roleModel();
        $schema = $user->getConnection()->getSchemaBuilder();
        if (!$schema->hasTable($role->getTable()) || !$schema->hasTable($user->roles()->getTable())) {
            $this->instructions($command, $choice, $package['trait']);
            $command->warn('Role tables are missing from the user connection. Complete migrations and run this command again. Existing role settings are unchanged.');

            return [];
        }
        $middleware = $command->option('role-middleware');
        if ($middleware === null && $interactive) {
            $default = config('laravelusers.rolesMiddlware', 'role:admin');
            $middleware = $command->ask('Role middleware (register the alias in your host application)', is_array($default) ? implode(';', $default) : $default);
        }
        $middleware = $middleware ?? config('laravelusers.rolesMiddlware', 'role:admin');
        if (is_string($middleware) && str_contains($middleware, ';')) {
            $middleware = array_values(array_filter(array_map('trim', explode(';', $middleware))));
        }
        if (!$middleware) {
            $command->error('Choose management middleware before enabling a roles package. Existing role settings are unchanged.');

            return false;
        }
        foreach ((array) $middleware as $entry) {
            $alias = explode(':', $entry, 2)[0];
            if (!isset($this->router->getMiddleware()[$alias]) && !isset($this->router->getMiddlewareGroups()[$alias]) && !class_exists($alias)) {
                $command->error('Register the '.$alias.' middleware alias before enabling roles. Existing role settings are unchanged.');

                return false;
            }
        }
        $command->info('Role selection enabled for '.$package['package'].'. Middleware: '.(is_array($middleware) ? implode(', ', $middleware) : $middleware));
        $command->line('Keep role tables on the user connection. Existing roles and permissions are preserved; no migrations or seeds are run.');

        return ['rolesEnabled' => true, 'roleModel' => $roleModel, 'rolesMiddlwareEnabled' => true, 'rolesMiddlware' => $middleware];
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
