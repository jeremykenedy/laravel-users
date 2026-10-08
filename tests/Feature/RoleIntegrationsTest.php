<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\LaravelRoles\Middleware\VerifyRole;
use jeremykenedy\LaravelRoles\RolesServiceProvider;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Test\Fixtures\PackageRoleUser;
use jeremykenedy\laravelusers\Test\Fixtures\SpatieRoleUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;

class RoleIntegrationsTest extends TestCase
{
    public function test_spatie_access_rules_do_not_cross_team_boundaries(): void
    {
        if (!class_exists(PermissionServiceProvider::class)) {
            $this->markTestSkipped('Run the optional roles integration job to test Spatie teams.');
        }
        $this->app->register(PermissionServiceProvider::class);
        config(['permission.teams' => true, 'laravelusers.settings.enabled' => true, 'laravelusers.defaultUserModel' => SpatieRoleUser::class, 'auth.providers.users.model' => SpatieRoleUser::class]);
        $this->app->make(PermissionRegistrar::class)->initializeCache();
        $this->spatieMigration();
        $one = Role::create(['name' => 'Team one', 'guard_name' => 'web', 'team_id' => 1]);
        $two = Role::create(['name' => 'Team two', 'guard_name' => 'web', 'team_id' => 2]);
        \setPermissionsTeamId(1);
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        $actor = SpatieRoleUser::findOrFail($this->user()->id);
        $actor->assignRole($one);
        config(['laravelusers.access.view_users' => ['mode' => 'restricted', 'roles' => [$two->id]], 'laravelusers.access.edit_settings' => ['mode' => 'restricted', 'roles' => [$one->id]]]);
        $this->actingAs($actor)->get('/users')->assertForbidden();
        $this->get('/users/settings')->assertOk()->assertSee('Team one')->assertDontSee('Team two');
        $this->put('/users/settings', ['avatar_source' => 'initials', 'profile_color' => '#2458b7', 'edit_color' => '#705000', 'access' => ['view_users' => ['mode' => 'restricted', 'roles' => [$two->id]]]])->assertSessionHasErrors('access.view_users.roles.0');
        config(['laravelusers.access.view_users' => ['mode' => 'restricted', 'roles' => [$one->id]]]);
        $this->get('/users')->assertOk();
        \setPermissionsTeamId(2);
        $this->actingAs($actor->fresh())->get('/users')->assertForbidden();
        \setPermissionsTeamId(null);
    }

    public function test_spatie_roles_are_optional_and_can_be_selected_saved_and_displayed(): void
    {
        if (!class_exists(PermissionServiceProvider::class)) {
            $this->markTestSkipped('Run the optional roles integration job to test Spatie.');
        }
        $this->app->register(PermissionServiceProvider::class);
        $this->app->make(PermissionRegistrar::class)->initializeCache();
        $this->spatieMigration();
        $this->exercise(SpatieRoleUser::class, Role::class, ['guard_name' => 'web']);
    }

    private function spatieMigration(): void
    {
        if (class_exists('CreatePermissionTables', false)) {
            (new \CreatePermissionTables())->up();

            return;
        }
        $migration = require dirname((new \ReflectionClass(PermissionServiceProvider::class))->getFileName(), 2).'/database/migrations/create_permission_tables.php.stub';
        (is_object($migration) ? $migration : new \CreatePermissionTables())->up();
    }

    public function test_laravel_roles_remains_optional_and_preserves_existing_assignment(): void
    {
        if (!class_exists(RolesServiceProvider::class)) {
            $this->markTestSkipped('Run the optional roles integration job to test Laravel Roles.');
        }
        config(['roles' => require dirname((new \ReflectionClass(RolesServiceProvider::class))->getFileName()).'/config/roles.php']);
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->integer('level')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->string('model')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        foreach (['permission_user' => 'user_id', 'permission_role' => 'role_id'] as $name => $key) {
            Schema::create($name, function (Blueprint $table) use ($key) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger($key);
                $table->timestamps();
            });
        }
        $this->exercise(PackageRoleUser::class, \jeremykenedy\LaravelRoles\Models\Role::class, []);
    }

    private function exercise(string $userModel, string $roleModel, array $attributes): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        config(['laravelusers.defaultUserModel' => $userModel, 'auth.providers.users.model' => $userModel, 'laravelusers.roleModel' => $roleModel, 'laravelusers.rolesEnabled' => true, 'laravelusers.rolesMiddlwareEnabled' => false, 'laravelusers.softDeletedEnabled' => true]);
        $legacy = !method_exists($userModel, 'assignRole');
        $admin = $roleModel::create(array_merge(['name' => 'Administrator'], $legacy ? ['slug' => 'administrator'] : [], $attributes));
        $editor = $roleModel::create(array_merge(['name' => 'Editor'], $legacy ? ['slug' => 'editor'] : [], $attributes));
        $actor = $userModel::findOrFail($this->user()->id);
        $this->actingAs($actor);
        $data = ['name' => 'roleuser', 'email' => 'role@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => (string) $admin->getKey()];
        $this->post('/users', $data)->assertSessionHasNoErrors()->assertRedirect('/users');
        $user = $userModel::where('name', 'roleuser')->firstOrFail();
        $this->assertSame([$admin->getKey()], $user->roles->modelKeys());
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => [(string) $editor->getKey(), (string) $admin->getKey()]])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$admin->getKey(), $editor->getKey()], $user->fresh()->roles->modelKeys());
        $this->postJson('/search-users', ['user_search_box' => 'roleuser'])->assertOk()->assertJsonCount(2, '0.roles');
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users')->assertOk()->assertSee('Administrator')->assertSee('Editor');
            $this->get('/users/'.$user->id)->assertOk()->assertSee('Administrator')->assertSee('Editor');
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('name="role[]"', false)->assertSee('multiple', false)->assertSee('selected');
            $user->delete();
            $this->get('/users/deleted')->assertOk()->assertSee('Administrator')->assertSee('Editor');
            $user->restore();
        }
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => [999]])->assertSessionHasErrors('role');
        $this->assertCount(2, $user->fresh()->roles);
        $this->exercisePermissions($userModel, $user, $admin, $legacy);
        $this->exerciseAccess($actor, $admin, $legacy);
        if (method_exists($actor, 'assignRole')) {
            $wrongGuard = $roleModel::create(['name' => 'ApiRole', 'guard_name' => 'api']);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertDontSee('ApiRole');
            $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => $wrongGuard->getKey()])->assertSessionHasErrors('role');
            $roleMiddleware = class_exists(RoleMiddleware::class) ? RoleMiddleware::class : \Spatie\Permission\Middlewares\RoleMiddleware::class;
            $permissionMiddleware = class_exists(PermissionMiddleware::class) ? PermissionMiddleware::class : \Spatie\Permission\Middlewares\PermissionMiddleware::class;
            $this->app['router']->aliasMiddleware('role', $roleMiddleware);
            $this->app['router']->aliasMiddleware('permission', $permissionMiddleware);
            $actor->assignRole($admin);
            $permission = Permission::create(['name' => 'manage users', 'guard_name' => 'web']);
            $actor->givePermissionTo($permission);
            config(['laravelusers.rolesMiddlwareEnabled' => true, 'laravelusers.rolesMiddlware' => ['role:Administrator', 'permission:manage users']]);
            foreach ($this->app['router']->getRoutes() as $route) {
                $route->flushController();
            }
            $this->get('/users')->assertOk();
            $actor->revokePermissionTo($permission);
            $this->get('/users')->assertForbidden();
            $this->post('/users/email/preview')->assertForbidden();
        }
        $this->exerciseInstaller($userModel, $roleModel);

    }

    private function exerciseAccess($actor, $role, bool $legacy): void
    {
        (require dirname(__DIR__, 2).'/src/database/settings/2026_10_08_145311_create_laravelusers_settings_table.php')->up();
        config(['laravelusers.settings.enabled' => true, 'laravelusers.access' => ['create_users' => ['mode' => 'restricted', 'roles' => [$role->getKey()]], 'edit_settings' => ['mode' => 'restricted', 'roles' => [$role->getKey()]]]]);
        $this->get('/users/create')->assertForbidden();
        $this->get('/users/settings')->assertForbidden();
        $legacy ? $actor->attachRole($role) : $actor->assignRole($role);
        $this->actingAs($actor->fresh())->get('/users/create')->assertOk();
        $this->get('/users/settings')->assertOk()->assertSee('Administrator');
        $this->exerciseImpersonation($actor->fresh(), $role);
        $this->from('/users/settings')->put('/users/settings', ['avatar_source' => 'initials', 'profile_color' => '#2458b7', 'edit_color' => '#705000', 'access' => ['edit_settings' => ['mode' => 'deny']]])->assertSessionHasErrors('access');
        $this->assertDatabaseCount('laravelusers_settings', 0);
        $permissionModel = $legacy ? \jeremykenedy\LaravelRoles\Models\Permission::class : Permission::class;
        $permission = $permissionModel::create(['name' => 'Manage directory'] + ($legacy ? ['slug' => 'manage-directory'] : ['guard_name' => 'web']));
        config(['laravelusers.access.view_users' => ['mode' => 'restricted', 'permissions' => [$permission->getKey()]]]);
        $this->get('/users')->assertForbidden();
        $actor->syncPermissions($legacy ? [$permission->getKey()] : [$permission]);
        $this->actingAs($actor->fresh())->get('/users')->assertOk();
        $actor->syncPermissions([]);
        $legacy ? $role->attachPermission($permission) : $role->givePermissionTo($permission);
        $this->actingAs($actor->fresh())->get('/users')->assertOk();
        $legacy ? $role->detachPermission($permission) : $role->revokePermissionTo($permission);
        $this->actingAs($actor->fresh())->get('/users')->assertForbidden();
        config(['laravelusers.access.view_users' => ['mode' => 'restricted']]);
        $this->get('/users')->assertForbidden();
        if ($legacy) {
            config(['laravelusers.access.view_users' => ['mode' => 'restricted', 'level' => 2]]);
            $this->get('/users')->assertForbidden();
            $role->update(['level' => 3]);
            $this->actingAs($actor->fresh())->get('/users')->assertOk();
            $role->update(['level' => 1]);
        } else {
            $wrongGuard = Permission::create(['name' => 'Wrong guard access', 'guard_name' => 'api']);
            config(['laravelusers.access.view_users' => ['mode' => 'restricted', 'permissions' => [$wrongGuard->getKey()]]]);
            $this->get('/users')->assertForbidden();
            config(['laravelusers.access.view_users' => ['mode' => 'inherit']]);
            $this->put('/users/settings', ['avatar_source' => 'initials', 'profile_color' => '#2458b7', 'edit_color' => '#705000', 'access' => ['view_users' => ['mode' => 'restricted', 'permissions' => [$wrongGuard->getKey()]]]])->assertSessionHasErrors('access.view_users.permissions.0');
        }
        $settings = ['avatar_source' => 'initials', 'profile_color' => '#264e36', 'edit_color' => '#705000', 'access' => ['edit_settings' => ['mode' => 'restricted', 'roles' => [$role->getKey()]], 'create_users' => ['mode' => 'deny']]];
        $this->actingAs($actor->fresh())->from('/users/settings')->put('/users/settings', $settings)->assertSessionHasNoErrors();
        $this->get('/users/create')->assertForbidden();
        $this->get('/users/settings')->assertOk();
        Schema::drop('laravelusers_settings');
        config(['laravelusers.settings.enabled' => false, 'laravelusers.access' => []]);
        $this->actingAs($actor);
    }

    private function exerciseImpersonation($actor, $role): void
    {
        (require dirname(__DIR__, 2).'/src/database/migrations/2026_10_07_000000_create_laravelusers_login_activity_table.php')->up();
        config(['laravelusers.impersonation.enabled' => true, 'laravelusers.access.impersonate_users' => ['mode' => 'restricted', 'roles' => [$role->getKey()]], 'laravelusers.activity.login' => true]);
        $target = $actor->newInstance(['name' => 'Temporary Account', 'email' => 'temporary@example.com', 'password' => bcrypt('password')]);
        $target->save();
        $unprivileged = $actor->newInstance(['name' => 'Unprivileged Account', 'email' => 'unprivileged@example.com', 'password' => bcrypt('password')]);
        $unprivileged->save();

        $this->actingAs($unprivileged)->post('/users/'.$target->getKey().'/impersonate')->assertNotFound();
        $this->actingAs($actor)->post('/users/'.$actor->getKey().'/impersonate')->assertForbidden();
        $this->withHeader('referer', 'http://localhost/users?page=2')->actingAs($actor)->post('/users/'.$target->getKey().'/impersonate')->assertRedirect('/');
        $this->assertAuthenticatedAs($target);
        $this->get('/users/'.$target->getKey())->assertOk()->assertSee('Impersonating Temporary Account')->assertSee('Exit impersonation');
        $this->assertNull($this->app->make(UserActivity::class)->lastLogin($target));
        $this->post('/users/'.$target->getKey().'/impersonate')->assertStatus(409);
        $this->post('/users/impersonation/stop')->assertRedirect('/users?page=2')->assertSessionHas('success');
        $this->assertAuthenticatedAs($actor);
        $this->assertNull($this->app->make(UserActivity::class)->lastLogin($target));
    }

    private function exercisePermissions(string $userModel, $user, $role, bool $legacy): void
    {
        $permissionModel = $legacy ? \jeremykenedy\LaravelRoles\Models\Permission::class : Permission::class;
        $direct = $permissionModel::create(['name' => 'Direct permission'] + ($legacy ? ['slug' => 'direct-permission'] : ['guard_name' => 'web']));
        $inherited = $permissionModel::create(['name' => 'Inherited permission'] + ($legacy ? ['slug' => 'inherited-permission'] : ['guard_name' => 'web']));
        $legacy ? $role->attachPermission($inherited) : $role->givePermissionTo($inherited);
        $relation = $legacy ? 'userPermissions' : 'permissions';
        config(['laravelusers.permissionsEnabled' => true, 'laravelusers.permissionModel' => $permissionModel]);
        $data = ['name' => $user->name, 'email' => $user->email, 'role' => [$role->getKey()], 'permissions_present' => 1, 'permissions' => [$direct->getKey()]];
        $this->put('/users/'.$user->id, $data)->assertSessionHasNoErrors();
        $this->assertSame([$direct->getKey()], $user->fresh()->$relation->modelKeys());
        foreach (['bootstrap4', 'bootstrap5', 'tailwind'] as $framework) {
            config(['laravelusers.frontend' => $framework]);
            $this->get('/users/create')->assertOk()->assertSee('name="permissions[]"', false);
            $this->get('/users/'.$user->id.'/edit')->assertOk()->assertSee('Direct permission')->assertSee('Inherited permission');
            $this->get('/users/'.$user->id)->assertOk()->assertSee('Direct permission');
            if ($legacy) {
                $this->get('/users/'.$user->id.'/edit')->assertSee('Level 1');
            }
        }
        $created = ['name' => 'PermissionUser', 'email' => 'permission@example.com', 'password' => 'password', 'password_confirmation' => 'password', 'role' => $role->getKey(), 'permissions' => [$direct->getKey()]];
        $this->post('/users', $created)->assertSessionHasNoErrors();
        $this->assertSame([$direct->getKey()], $userModel::where('name', 'PermissionUser')->firstOrFail()->$relation->modelKeys());
        Event::listen('eloquent.created: '.$userModel, function ($createdUser) use ($direct, $legacy) {
            $createdUser->syncPermissions($legacy ? [$direct->getKey()] : [$direct]);
        });

        try {
            unset($created['permissions']);
            $created['name'] = 'ObserverPermissionUser';
            $created['email'] = 'observer-permission@example.com';
            $this->post('/users', $created)->assertSessionHasNoErrors();
            $this->assertSame([$direct->getKey()], $userModel::where('name', $created['name'])->firstOrFail()->$relation->modelKeys());
        } finally {
            Event::forget('eloquent.created: '.$userModel);
        }
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => [$role->getKey()], 'permissions_present' => 0])->assertSessionHasNoErrors();
        $this->assertSame([$direct->getKey()], $user->fresh()->$relation->modelKeys());
        $this->put('/users/'.$user->id, ['name' => $user->name, 'email' => $user->email, 'role' => [$role->getKey()]])->assertSessionHasNoErrors();
        $this->assertSame([$direct->getKey()], $user->fresh()->$relation->modelKeys());
        $this->put('/users/'.$user->id, array_merge($data, ['name' => 'InvalidChange', 'permissions' => [999999]]))->assertSessionHasErrors('permissions');
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame([$direct->getKey()], $user->fresh()->$relation->modelKeys());
        if (!$legacy) {
            $wrong = $permissionModel::create(['name' => 'API permission', 'guard_name' => 'api']);
            $this->get('/users/'.$user->id.'/edit')->assertDontSee('API permission');
            $this->put('/users/'.$user->id, array_merge($data, ['permissions' => [$wrong->getKey()]]))->assertSessionHasErrors('permissions');
        }
        Gate::define('assign-user-permissions', fn () => false);
        config(['laravelusers.permissionsGate' => 'assign-user-permissions']);
        $this->put('/users/'.$user->id, array_merge($data, ['name' => 'Unauthorized']))->assertForbidden();
        $this->assertSame($user->name, $user->fresh()->name);
        config(['laravelusers.permissionsGate' => null]);
        unset($data['permissions']);
        $this->put('/users/'.$user->id, $data)->assertSessionHasNoErrors();
        $this->assertCount(0, $user->fresh()->$relation);
        $this->assertSame([$inherited->getKey()], $role->fresh()->permissions->modelKeys());
        $this->assertSame(1, $legacy ? $role->fresh()->level : 1);
        config(['laravelusers.permissionsEnabled' => false]);
        $this->put('/users/'.$user->id, array_merge($data, ['permissions' => [$direct->getKey()]]))->assertSessionHasNoErrors();
        $this->assertCount(0, $user->fresh()->$relation);
        $this->get('/users/create')->assertDontSee('name="permissions[]"', false);
    }

    private function exerciseInstaller(string $userModel, string $roleModel): void
    {
        $directory = sys_get_temp_dir().'/role-install-'.bin2hex(random_bytes(8));
        $basePath = $this->app->basePath();
        $storagePath = $this->app->storagePath();
        $files = new Filesystem();
        $this->app->setBasePath($directory);
        $this->app->useStoragePath($directory.'/storage');
        $files->ensureDirectoryExists(storage_path('framework/views'));
        $files->ensureDirectoryExists(config_path());
        $files->put(config_path('laravelusers.php'), '<?php return ["custom" => true];');
        $choice = method_exists($userModel, 'assignRole') ? 'spatie' : 'laravel-roles';
        $this->app['router']->aliasMiddleware('role', $choice === 'spatie' ? (class_exists(RoleMiddleware::class) ? RoleMiddleware::class : \Spatie\Permission\Middlewares\RoleMiddleware::class) : VerifyRole::class);

        try {
            config(['laravelusers.defaultUserModel' => User::class]);
            $this->artisan('laravelusers:update', ['--roles' => $choice, '--no-interaction' => true])->assertExitCode(0);
            $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
            config(['laravelusers.defaultUserModel' => $userModel]);
            $this->artisan('laravelusers:update', ['--roles' => $choice, '--role-middleware' => 'missing-role-alias:admin', '--no-interaction' => true])->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
            $this->artisan('laravelusers:update', ['--roles' => $choice, '--role-middleware' => 'role:Administrator;auth', '--no-interaction' => true])->assertExitCode(0);
            $settings = require config_path('laravelusers-roles.php');
            $this->assertTrue($settings['rolesEnabled']);
            $this->assertSame($roleModel, $settings['roleModel']);
            $this->assertSame(['role:Administrator', 'auth'], $settings['rolesMiddlware']);
            $this->assertSame('<?php return ["custom" => true];', $files->get(config_path('laravelusers.php')));
            $this->assertDirectoryDoesNotExist(database_path('migrations'));
            $this->app['router']->middlewareGroup('user-managers', ['auth']);
            $this->artisan('laravelusers:update', ['--roles' => $choice, '--role-middleware' => 'user-managers', '--no-interaction' => true])->assertExitCode(0);
            $settings = require config_path('laravelusers-roles.php');
            $this->assertSame('user-managers', $settings['rolesMiddlware']);
        } finally {
            $this->app->setBasePath($basePath);
            $this->app->useStoragePath($storagePath);
            $files->deleteDirectory($directory);
        }
    }
}
