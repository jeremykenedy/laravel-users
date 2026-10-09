<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Test\Fixtures;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\LaravelRoles\RolesServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;

class NativeRoleBrowser
{
    public function boot(Application $app, string $integration): void
    {
        $spatie = $integration === 'spatie';
        $provider = $spatie ? PermissionServiceProvider::class : RolesServiceProvider::class;
        abort_unless(class_exists($provider), 503);
        $database = dirname(__DIR__).'/browser/runtime/native-'.$integration.'.sqlite';
        if (!is_file($database)) {
            touch($database);
        }
        $userModel = $spatie ? SpatieRoleUser::class : PackageRoleUser::class;
        $roleModel = $spatie ? Role::class : \jeremykenedy\LaravelRoles\Models\Role::class;
        $permissionModel = $spatie ? Permission::class : \jeremykenedy\LaravelRoles\Models\Permission::class;
        config([
            'database.connections.testing.database' => $database,
            'auth.providers.users.model'            => $userModel,
            'laravelusers.defaultUserModel'         => $userModel,
            'laravelusers.rolesEnabled'             => true,
            'laravelusers.rolesMiddlwareEnabled'    => false,
            'laravelusers.roleModel'                => $roleModel,
            'laravelusers.permissionsEnabled'       => true,
            'laravelusers.permissionModel'          => $permissionModel,
            'roles.rolesGuiEnabled'                 => false,
        ]);
        DB::purge('testing');
        $app['auth']->forgetGuards();
        $app->register($provider);
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->softDeletes();
            });
            $userModel::create(['name' => 'Morgan Hayes', 'email' => 'user0@example.com', 'password' => bcrypt('password')]);
            $userModel::create(['name' => 'Alex Rivers', 'email' => 'user1@example.com', 'password' => bcrypt('password')]);
        }
        $directory = dirname((new \ReflectionClass($provider))->getFileName());
        if ($spatie) {
            $app->make(PermissionRegistrar::class)->initializeCache();
            if (!Schema::hasTable('roles')) {
                (require dirname($directory).'/database/migrations/create_permission_tables.php.stub')->up();
            }
        } else {
            foreach (glob($directory.'/database/Migrations/*.php') as $migration) {
                (require $migration)->up();
            }
        }
        $attributes = $spatie ? ['guard_name' => 'web'] : [];
        $administrator = $roleModel::firstOrCreate(['name' => 'Administrator'] + $attributes, $spatie ? [] : ['slug' => 'administrator', 'level' => 3]);
        $roleModel::firstOrCreate(['name' => 'Editor'] + $attributes, $spatie ? [] : ['slug' => 'editor', 'level' => 1]);
        $permissionModel::firstOrCreate(['name' => 'Direct permission'] + $attributes, $spatie ? [] : ['slug' => 'direct-permission']);
        $inherited = $permissionModel::firstOrCreate(['name' => 'Inherited permission'] + $attributes, $spatie ? [] : ['slug' => 'inherited-permission']);
        if (!$administrator->permissions->contains($inherited)) {
            $spatie ? $administrator->givePermissionTo($inherited) : $administrator->attachPermission($inherited);
        }
        $actor = $userModel::findOrFail(1);
        if (!$actor->roles->contains($administrator)) {
            $spatie ? $actor->assignRole($administrator) : $actor->attachRole($administrator);
        }
        if ($spatie) {
            $roleModel::firstOrCreate(['name' => 'API role', 'guard_name' => 'api']);
            $permissionModel::firstOrCreate(['name' => 'API permission', 'guard_name' => 'api']);
        }
        if (($_COOKIE['lu-native-permissions-denied'] ?? '0') === '1') {
            config(['laravelusers.permissionsGate' => 'native-assign-permissions']);
            Gate::define('native-assign-permissions', fn () => false);
        }
        foreach ($app['router']->getRoutes() as $route) {
            $route->flushController();
        }
        Auth::forgetGuards();
    }
}
