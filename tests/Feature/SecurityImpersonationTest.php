<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\LaravelRoles\App\Http\Middleware\VerifyLevel;
use jeremykenedy\LaravelRoles\Models\Role;
use jeremykenedy\LaravelRoles\RolesServiceProvider;
use jeremykenedy\laravelusers\Test\Fixtures\PackageRoleUser;
use jeremykenedy\laravelusers\Test\TestCase;

class SecurityImpersonationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(RolesServiceProvider::class)) {
            $this->markTestSkipped('This regression requires the optional Laravel Roles integration.');
        }
        config([
            'roles'                              => require dirname((new \ReflectionClass(RolesServiceProvider::class))->getFileName()).'/config/roles.php',
            'laravelusers.defaultUserModel'      => PackageRoleUser::class,
            'auth.providers.users.model'         => PackageRoleUser::class,
            'laravelusers.roleModel'             => Role::class,
            'laravelusers.rolesEnabled'          => true,
            'laravelusers.rolesMiddlwareEnabled' => false,
            'laravelusers.impersonation.enabled' => true,
        ]);
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->integer('level')->default(1);
            $table->timestamps();
            $table->softDeletes();
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
        foreach (['role_user' => ['role_id', 'user_id'], 'permission_user' => ['permission_id', 'user_id'], 'permission_role' => ['permission_id', 'role_id']] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->unsignedBigInteger($column);
                }
                $table->timestamps();
            });
        }
        Route::get('/security-host-account', fn () => response()->json(['id' => Auth::id()]))->middleware(['web', 'auth']);
    }

    public function test_revoked_impersonation_gate_ends_access_to_the_target(): void
    {
        config(['laravelusers.impersonation.middleware' => ['can:support-impersonation']]);
        Gate::define('support-impersonation', fn () => true);
        [$actor, $target] = $this->accounts();
        $this->actingAs($actor)->post('/users/'.$target->id.'/impersonate')->assertRedirect('/');
        $this->assertAuthenticatedAs($target);
        Gate::define('support-impersonation', fn () => false);

        $this->get('/security-host-account')->assertRedirect('/users');

        $this->assertAuthenticatedAs($actor);
    }

    public function test_revoked_management_level_ends_access_to_the_target(): void
    {
        $this->app['router']->aliasMiddleware('level', VerifyLevel::class);
        config(['laravelusers.rolesMiddlwareEnabled' => true, 'laravelusers.rolesMiddlware' => 'level:50']);
        [$actor, $target, $role] = $this->accounts();
        $this->actingAs($actor)->post('/users/'.$target->id.'/impersonate')->assertRedirect('/');
        $this->assertAuthenticatedAs($target);
        $role->update(['level' => 1]);

        $this->get('/security-host-account')->assertRedirect('/users');

        $this->assertAuthenticatedAs($actor);
    }

    private function accounts(): array
    {
        $role = Role::create(['name' => 'Administrator', 'slug' => 'administrator', 'level' => 100]);
        $actor = PackageRoleUser::findOrFail($this->user()->id);
        $target = PackageRoleUser::findOrFail($this->user()->id);
        $actor->attachRole($role);

        return [$actor, $target, $role];
    }
}
