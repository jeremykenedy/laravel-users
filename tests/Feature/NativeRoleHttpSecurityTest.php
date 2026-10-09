<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use CreatePermissionTables;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Support\NativeRuntime;
use jeremykenedy\laravelusers\Test\Fixtures\SpatieRoleUser;
use jeremykenedy\laravelusers\Test\TestCase;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use ReflectionClass;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;

/**
 * Integration fixtures exercise the framework types and optional providers used by this feature.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class NativeRoleHttpSecurityTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $providers = parent::getPackageProviders($app);
        foreach ([LivewireServiceProvider::class, PermissionServiceProvider::class] as $provider) {
            if (class_exists($provider)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        if (!class_exists(PermissionServiceProvider::class)) {
            return;
        }
        $app['router']->aliasMiddleware('native-security-role', class_exists(RoleMiddleware::class) ? RoleMiddleware::class : \Spatie\Permission\Middlewares\RoleMiddleware::class);
        $app['router']->aliasMiddleware('native-security-permission', class_exists(PermissionMiddleware::class) ? PermissionMiddleware::class : \Spatie\Permission\Middlewares\PermissionMiddleware::class);
        $app['config']->set('laravelusers.defaultUserModel', SpatieRoleUser::class);
        $app['config']->set('auth.providers.users.model', SpatieRoleUser::class);
        $app['config']->set('laravelusers.roleModel', Role::class);
        $app['config']->set('laravelusers.rolesEnabled', true);
        $app['config']->set('laravelusers.rolesMiddlwareEnabled', true);
        $app['config']->set('laravelusers.rolesMiddlware', ['native-security-role:administrator,web', 'native-security-permission:manage-users,web']);
        $app['config']->set('laravelusers.runtime', 'livewire');
        $app['config']->set('laravelusers-ui.runtime', 'livewire');
        $app['config']->set('permission.testing', true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(NativeRuntime::class) || !class_exists(LivewireServiceProvider::class) || !class_exists(PermissionServiceProvider::class)) {
            $this->markTestSkipped('This regression requires the optional Livewire and Spatie integrations.');
        }
        $this->app->instance('env', 'local');
        $this->app->make(PermissionRegistrar::class)->initializeCache();
        $migration = class_exists('CreatePermissionTables', false)
            ? new CreatePermissionTables()
            : require dirname((new ReflectionClass(PermissionServiceProvider::class))->getFileName(), 2).'/database/migrations/create_permission_tables.php.stub';
        (is_object($migration) ? $migration : new CreatePermissionTables())->up();
        Schema::table('users', fn (Blueprint $table) => $table->softDeletes());
    }

    public function test_livewire_update_rechecks_a_configured_role_after_revocation(): void
    {
        $this->assertRevoked(fn (SpatieRoleUser $actor) => $actor->removeRole('administrator'));
    }

    public function test_livewire_update_rechecks_a_configured_permission_after_revocation(): void
    {
        $this->assertRevoked(fn (SpatieRoleUser $actor) => $actor->revokePermissionTo('manage-users'));
    }

    private function assertRevoked(callable $revoke): void
    {
        $actor = SpatieRoleUser::findOrFail($this->user()->id);
        $actor->assignRole(Role::create(['name' => 'administrator', 'guard_name' => 'web']));
        $actor->givePermissionTo(Permission::create(['name' => 'manage-users', 'guard_name' => 'web']));
        $this->withSession(['_token' => 'native-role-security-token']);
        $html = $this->actingAs($actor)->get('/users')->assertOk()->getContent();
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        $snapshot = null;
        foreach ($matches[1] as $value) {
            $candidate = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ((json_decode($candidate, true)['memo']['name'] ?? null) === 'laravelusers.user-table') {
                $snapshot = $candidate;

                break;
            }
        }
        $this->assertNotNull($snapshot, 'The native user table did not return a signed Livewire snapshot.');
        $payload = ['components' => [['snapshot' => $snapshot, 'updates' => ['filter' => 'person'], 'calls' => []]]];
        $headers = ['X-Livewire' => 'true', 'X-CSRF-TOKEN' => 'native-role-security-token'];
        $this->postJson(Livewire::getUpdateUri(), $payload, $headers)->assertOk();
        Livewire::flushState();
        $revoke($actor);

        $this->postJson(Livewire::getUpdateUri(), $payload, $headers)->assertForbidden();
    }
}
