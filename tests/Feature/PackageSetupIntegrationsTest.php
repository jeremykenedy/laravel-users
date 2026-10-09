<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\LaravelRoles\RolesServiceProvider;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Test\TestCase;
use Spatie\Permission\PermissionServiceProvider;

class PackageSetupIntegrationsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-package-setup-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        $files = new Filesystem();
        $files->ensureDirectoryExists(storage_path('framework/views'));
        $files->ensureDirectoryExists(database_path('migrations'));
        $files->put(database_path('migrations/2020_01_01_000000_host_migration.php'), <<<'PHP'
<?php
return new class extends \Illuminate\Database\Migrations\Migration {
    public function up(): void
    {
        throw new \RuntimeException('Host migrations must not run during package setup.');
    }
};
PHP);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_spatie_setup_preserves_host_configuration_and_only_runs_package_migrations(): void
    {
        $this->setupRoles('spatie', PermissionServiceProvider::class, 'permission');
        $this->assertTrue(Schema::hasTable('model_has_roles'));
        $this->assertTrue(Schema::hasTable('model_has_permissions'));
    }

    public function test_laravel_roles_setup_preserves_host_configuration_and_only_runs_package_migrations(): void
    {
        $this->setupRoles('laravel-roles', RolesServiceProvider::class, 'roles');
        $this->assertTrue(Schema::hasTable('role_user'));
        $this->assertTrue(Schema::hasTable('permission_user'));
    }

    private function setupRoles(string $package, string $provider, string $configuration): void
    {
        if (!class_exists($provider)) {
            $this->markTestSkipped('Run the optional integration job to test package setup.');
        }
        $this->app->register($provider);
        $options = ['package' => $package, '--migrate' => true, '--no-interaction' => true];
        $this->artisan('laravelusers:setup-package', $options)->assertExitCode(0);
        $this->assertFileExists(config_path($configuration.'.php'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        $this->assertFalse(config('laravelusers.rolesEnabled'));
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
        $files = new Filesystem();
        $custom = "<?php return ['host_setting' => 'preserved'];\n";
        $files->put(config_path($configuration.'.php'), $custom);
        $this->artisan('laravelusers:setup-package', $options)->assertExitCode(0);
        $this->assertSame($custom, $files->get(config_path($configuration.'.php')));
    }

    public function test_toast_setup_uses_the_selected_framework_and_preserves_existing_settings(): void
    {
        if (!class_exists(ToastServiceProvider::class)) {
            $this->markTestSkipped('Run the optional integration job to test Toast setup.');
        }
        $this->app->register(ToastServiceProvider::class);
        $options = ['package' => 'toast', '--framework' => 'bootstrap5', '--no-interaction' => true];
        $this->artisan('laravelusers:setup-package', $options)->assertExitCode(0);
        $this->assertFileExists(config_path('toast.php'));
        $this->assertSame('bootstrap5', config('toast.css_framework'));
        $this->assertSame('blade', config('toast.frontend'));
        $this->assertSame('alert', config('laravelusers.notifications.driver'));
        $files = new Filesystem();
        $custom = "<?php return ['host_setting' => 'preserved'];\n";
        $files->put(config_path('toast.php'), $custom);
        $this->artisan('laravelusers:setup-package', $options)->assertExitCode(0);
        $this->assertSame($custom, $files->get(config_path('toast.php')));
    }

    public function test_invalid_setup_options_and_cached_configuration_do_not_publish_files(): void
    {
        $this->artisan('laravelusers:setup-package', ['package' => 'unknown', '--no-interaction' => true])->assertExitCode(1);
        $this->mock(ManagedPackages::class)->shouldReceive('installed')->with('toast')->andReturn(true);
        $this->artisan('laravelusers:setup-package', ['package' => 'toast', '--framework' => 'unknown', '--no-interaction' => true])->assertExitCode(1);
        $files = new Filesystem();
        $cached = $this->app->getCachedConfigPath();
        $files->ensureDirectoryExists(dirname($cached));
        $files->put($cached, '<?php return [];');
        $this->app->forgetInstance('config_loaded_from_cache');

        try {
            $this->artisan('laravelusers:setup-package', ['package' => 'toast', '--no-interaction' => true])->assertExitCode(1);
            $this->assertDirectoryDoesNotExist(config_path());
        } finally {
            $files->delete($cached);
        }
    }
}
