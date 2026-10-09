<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use CreatePermissionTables;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyLevel;
use jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyPermission;
use jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyRole;
use jeremykenedy\LaravelRoles\Models\Permission as LaravelPermission;
use jeremykenedy\LaravelRoles\Models\Role as LaravelRole;
use jeremykenedy\LaravelRoles\RolesServiceProvider;
use jeremykenedy\laravelusers\Support\MiddlewareAccess;
use jeremykenedy\laravelusers\Test\Fixtures\PackageRoleUser;
use jeremykenedy\laravelusers\Test\Fixtures\SpatieRoleUser;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;
use ReflectionClass;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;

class MiddlewareAccessTest extends TestCase
{
    public function test_gate_rechecks_use_the_actor_without_replacing_the_current_user(): void
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($target);
        Gate::define('security-manage', fn ($user) => $user->is($actor));

        $this->assertTrue(MiddlewareAccess::allows($actor, ['can:security-manage'], $target));
        $this->assertFalse(MiddlewareAccess::allows($target, ['can:security-manage'], $actor));
        $this->assertTrue(Auth::user()->is($target));

        Gate::define('security-manage', fn () => false);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['can:security-manage'], $target));
        $this->assertTrue(Auth::user()->is($target));
    }

    public function test_registered_class_aliases_preserve_gate_authorization(): void
    {
        $actor = $this->user();
        Route::aliasMiddleware('security-authorize', Authorize::class);
        Gate::define('security-manage', fn ($user) => $user->is($actor));

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-authorize:security-manage']));
        $this->assertTrue(MiddlewareAccess::allows($actor, [Authorize::class.':security-manage']));

        Gate::define('security-manage', fn () => false);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-authorize:security-manage']));
        $this->assertFalse(MiddlewareAccess::allows($actor, [Authorize::class.':security-manage']));
    }

    public function test_gate_arguments_preserve_literals_and_class_names_and_reject_unresolved_bindings(): void
    {
        $this->app->make(Kernel::class);
        $actor = $this->user();
        Gate::define('security-context', fn ($user, $context = null) => $user->is($actor) && in_array($context, [User::class, 'billing'], true));

        $this->assertTrue(MiddlewareAccess::allows($actor, ['can:security-context,'.User::class]));
        $this->assertTrue(MiddlewareAccess::allows($actor, ['can:security-context,"billing"']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['can:security-context,"other"']));
        Gate::define('security-context', fn () => true);
        $this->assertFalse(MiddlewareAccess::allows($actor, ['can:security-context,user']));
    }

    public function test_nested_groups_require_every_restriction_and_reject_cycles(): void
    {
        $this->app->make(Kernel::class);
        $actor = $this->user();
        Route::middlewareGroup('security-inner', ['can:security-active']);
        Route::middlewareGroup('security-outer', ['security-inner', 'can:security-manage']);
        Gate::define('security-active', fn () => true);
        Gate::define('security-manage', fn () => true);

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-outer']));

        Gate::define('security-active', fn () => false);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-outer']));
        Route::middlewareGroup('security-cycle-one', ['security-cycle-two']);
        Route::middlewareGroup('security-cycle-two', ['security-cycle-one']);
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-cycle-one']));
    }

    public function test_unknown_restrictions_fail_closed_without_running_http_middleware(): void
    {
        $actor = $this->user();
        NeverRunSecurityMiddleware::$calls = 0;
        Route::aliasMiddleware('security-custom', NeverRunSecurityMiddleware::class);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-custom:active']));
        $this->assertFalse(MiddlewareAccess::allows($actor, [NeverRunSecurityMiddleware::class.':active']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['missing-security-middleware']));
        $this->assertSame(0, NeverRunSecurityMiddleware::$calls);
    }

    public function test_a_replaced_builtin_alias_cannot_bypass_its_custom_restriction(): void
    {
        $this->app->make(Kernel::class);
        $actor = $this->user();
        Route::aliasMiddleware('can', NeverRunSecurityMiddleware::class);
        Gate::define('security-manage', fn () => true);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['can:security-manage']));
    }

    public function test_mapped_custom_gate_receives_actor_target_and_arguments_and_observes_revocation(): void
    {
        $actor = $this->user();
        $target = $this->user();
        $this->actingAs($target);
        NeverRunSecurityMiddleware::$calls = 0;
        Route::aliasMiddleware('security-custom', NeverRunSecurityMiddleware::class);
        config(['laravelusers.authorization.middleware_gates' => ['security-custom' => 'security-custom-access']]);
        $received = [];
        Gate::define('security-custom-access', function ($user, $selected, $arguments) use (&$received, $actor, $target) {
            $received = [$user->getKey(), $selected->getKey(), $arguments];

            return $user->is($actor) && $selected->is($target) && $arguments === ['active', 'billing'];
        });

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-custom:active,billing'], $target));
        $this->assertSame([$actor->getKey(), $target->getKey(), ['active', 'billing']], $received);
        Gate::define('security-custom-access', fn () => false);
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-custom:active,billing'], $target));
        $this->assertSame(0, NeverRunSecurityMiddleware::$calls);
        $this->assertTrue(Auth::user()->is($target));
    }

    public function test_custom_class_gate_mapping_supports_jobs_without_a_target(): void
    {
        $actor = $this->user();
        Route::aliasMiddleware('security-custom', NeverRunSecurityMiddleware::class);
        config(['laravelusers.authorization.middleware_gates' => [NeverRunSecurityMiddleware::class => 'security-job-access']]);
        Gate::define('security-job-access', fn ($user, ?Model $target, array $arguments) => $user->is($actor) && $target === null && $arguments === ['active']);

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-custom:active']));
        $this->assertTrue(MiddlewareAccess::allows($actor, [NeverRunSecurityMiddleware::class.':active']));

        config(['laravelusers.authorization.middleware_gates' => [NeverRunSecurityMiddleware::class => 'undefined-security-gate']]);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-custom:active']));
    }

    public function test_custom_mappings_cannot_override_a_known_denied_gate(): void
    {
        $this->app->make(Kernel::class);
        $actor = $this->user();
        Gate::define('security-denied', fn () => false);
        Gate::define('security-allow', fn () => true);
        config(['laravelusers.authorization.middleware_gates' => ['can' => 'security-allow', Authorize::class => 'security-allow']]);

        $this->assertFalse(MiddlewareAccess::allows($actor, ['can:security-denied']));
        $this->assertFalse(MiddlewareAccess::allows($actor, [Authorize::class.':security-denied']));
    }

    public function test_laravel_role_and_permission_arguments_match_the_installed_middleware(): void
    {
        $actor = $this->laravelRoleActor();
        Route::aliasMiddleware('security-role', VerifyRole::class);
        Route::aliasMiddleware('security-permission', VerifyPermission::class);
        $role = LaravelRole::create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 100]);
        $literal = LaravelRole::create(['name' => 'Literal True', 'slug' => 'true']);
        $permission = LaravelPermission::create(['name' => 'Manage', 'slug' => 'manage']);
        $literalPermission = LaravelPermission::create(['name' => 'Literal True', 'slug' => 'true']);
        $actor->attachRole($role);
        $actor->attachRole($literal);
        $actor->syncPermissions([$permission->id, $literalPermission->id]);

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-role:missing,admin']));
        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-role:missing,true']));
        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-permission:missing,manage']));
        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-permission:missing,true']));
        $this->assertTrue(MiddlewareAccess::allows($actor, [VerifyRole::class.':missing|admin', VerifyPermission::class.':missing|manage']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-role:missing,other']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-permission:missing,other']));
    }

    public function test_laravel_level_rechecks_follow_role_changes(): void
    {
        $actor = $this->laravelRoleActor();
        Route::aliasMiddleware('security-level', VerifyLevel::class);
        $role = LaravelRole::create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 100]);
        $actor->attachRole($role);

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-level:50']));
        $this->assertTrue(MiddlewareAccess::allows($actor, [VerifyLevel::class.':100']));

        $role->update(['level' => 1]);

        $this->assertFalse(MiddlewareAccess::allows($actor->fresh(), ['security-level:50']));
        $this->assertFalse(MiddlewareAccess::allows($actor->fresh(), [VerifyLevel::class.':100']));
    }

    public function test_spatie_guard_arguments_cannot_become_role_or_permission_choices(): void
    {
        $actor = $this->spatieActor();
        $roleMiddleware = class_exists(RoleMiddleware::class) ? RoleMiddleware::class : \Spatie\Permission\Middlewares\RoleMiddleware::class;
        $permissionMiddleware = class_exists(PermissionMiddleware::class) ? PermissionMiddleware::class : \Spatie\Permission\Middlewares\PermissionMiddleware::class;
        Route::aliasMiddleware('security-role', $roleMiddleware);
        Route::aliasMiddleware('security-permission', $permissionMiddleware);
        $actor->assignRole(SpatieRole::create(['name' => 'api', 'guard_name' => 'web']));
        $actor->givePermissionTo(SpatiePermission::create(['name' => 'api', 'guard_name' => 'web']));

        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-role:administrator,api']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-permission:manage-users,api']));
        $actor->assignRole(SpatieRole::create(['name' => 'administrator', 'guard_name' => 'web']));
        $actor->givePermissionTo(SpatiePermission::create(['name' => 'manage-users', 'guard_name' => 'web']));

        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-role:administrator,web']));
        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-permission:manage-users,web']));
        $this->assertTrue(MiddlewareAccess::allows($actor, [$roleMiddleware.':missing|administrator,web', $permissionMiddleware.':missing|manage-users,web']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-role:administrator,api']));
        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-permission:manage-users,api']));
    }

    /**
     * Gate callbacks receive the user before the requested ability.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function test_spatie_permission_checks_honor_host_gate_revocation(): void
    {
        $revoked = false;
        Gate::before(function ($user, $ability) use (&$revoked) {
            return $ability === 'manage-users' && $revoked ? false : null;
        });
        $actor = $this->spatieActor();
        $middleware = class_exists(PermissionMiddleware::class) ? PermissionMiddleware::class : \Spatie\Permission\Middlewares\PermissionMiddleware::class;
        Route::aliasMiddleware('security-permission', $middleware);
        $actor->givePermissionTo(SpatiePermission::create(['name' => 'manage-users', 'guard_name' => 'web']));
        $this->assertTrue(MiddlewareAccess::allows($actor, ['security-permission:manage-users,web']));
        $revoked = true;

        $this->assertFalse(MiddlewareAccess::allows($actor, ['security-permission:manage-users,web']));
    }

    private function laravelRoleActor(): PackageRoleUser
    {
        if (!class_exists(RolesServiceProvider::class)) {
            $this->markTestSkipped('This regression requires the optional Laravel Roles integration.');
        }
        config(['roles' => require dirname((new ReflectionClass(RolesServiceProvider::class))->getFileName()).'/config/roles.php']);
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        foreach (['roles', 'permissions'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                $table->string('name');
                $table->string('slug');
                if ($name === 'roles') {
                    $table->integer('level')->default(1);
                } else {
                    $table->string('description')->nullable();
                    $table->string('model')->nullable();
                }
                $table->timestamps();
                $table->softDeletes();
            });
        }
        foreach (['role_user' => ['role_id', 'user_id'], 'permission_user' => ['permission_id', 'user_id'], 'permission_role' => ['permission_id', 'role_id']] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->unsignedBigInteger($column);
                }
                $table->timestamps();
            });
        }

        return PackageRoleUser::findOrFail($this->user()->id);
    }

    private function spatieActor(): SpatieRoleUser
    {
        if (!class_exists(PermissionServiceProvider::class)) {
            $this->markTestSkipped('This regression requires the optional Spatie integration.');
        }
        $this->app->register(PermissionServiceProvider::class);
        config(['permission.testing' => true]);
        $this->app->make(PermissionRegistrar::class)->initializeCache();
        if (class_exists('CreatePermissionTables', false)) {
            (new CreatePermissionTables())->up();
        } else {
            $migration = require dirname((new ReflectionClass(PermissionServiceProvider::class))->getFileName(), 2).'/database/migrations/create_permission_tables.php.stub';
            (is_object($migration) ? $migration : new CreatePermissionTables())->up();
        }
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());

        return SpatieRoleUser::findOrFail($this->user()->id);
    }
}

class NeverRunSecurityMiddleware
{
    public static int $calls = 0;

    public function handle($request, $next)
    {
        self::$calls++;

        return $next($request);
    }
}
