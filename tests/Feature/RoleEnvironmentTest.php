<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Dotenv\Dotenv;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Support\RolesSetup;
use jeremykenedy\laravelusers\Test\TestCase;

class RoleEnvironmentTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-role-environment-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useEnvironmentPath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        (new Filesystem())->ensureDirectoryExists(storage_path('framework/views'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_explicit_ready_role_selection_replaces_prior_environment_opt_out_and_package_values(): void
    {
        $path = $this->app->environmentFilePath();
        file_put_contents($path, "APP_NAME='Preserved host'\nLARAVEL_USERS_ROLES_ENABLED=false\nLARAVEL_USERS_ROLE_MODEL='Host\\OldRole'\nLARAVEL_USERS_ROLES_MIDDLWARE_ENABLED=false\nLARAVEL_USERS_ROLES_MIDDLWARE='old-role:admin'\n");
        chmod($path, 0600);
        $settings = ['rolesEnabled' => true, 'roleModel' => 'Host\\SelectedRole', 'rolesMiddlwareEnabled' => true, 'rolesMiddlware' => 'role:Administrator'];
        $this->mock(RolesSetup::class)->shouldReceive('configure')->twice()->andReturn($settings);

        $this->artisan('laravelusers:update', ['--roles' => 'spatie', '--no-interaction' => true])->assertExitCode(0);

        $values = Dotenv::parse(file_get_contents($path));
        $this->assertSame('true', $values['LARAVEL_USERS_ROLES_ENABLED']);
        $this->assertSame($settings['roleModel'], $values['LARAVEL_USERS_ROLE_MODEL']);
        $this->assertSame('true', $values['LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED']);
        $this->assertSame($settings['rolesMiddlware'], $values['LARAVEL_USERS_ROLES_MIDDLWARE']);
        $this->assertSame('Preserved host', $values['APP_NAME']);
        clearstatcache(true, $path);
        $this->assertSame(0600, fileperms($path) & 0777);

        $target = $this->directory.'/.protected-environment';
        rename($path, $target);
        symlink($target, $path);
        file_put_contents($target, "APP_NAME='Preserved host'\nLARAVEL_USERS_ROLES_ENABLED=false\n");

        $this->artisan('laravelusers:update', ['--roles' => 'spatie', '--no-interaction' => true])->assertExitCode(0);

        clearstatcache(true, $target);
        $this->assertTrue(is_link($path));
        $this->assertSame(realpath($target), realpath($path));
        $this->assertSame('true', Dotenv::parse(file_get_contents($target))['LARAVEL_USERS_ROLES_ENABLED']);
        $this->assertSame(0600, fileperms($target) & 0777);
        $this->assertSame([], glob($this->directory.'/.laravelusers-env-*'));
    }

    public function test_failed_environment_replacement_keeps_the_original_and_removes_the_private_temporary_file(): void
    {
        $path = $this->app->environmentFilePath();
        $contents = "APP_KEY=private-host-secret\nLARAVEL_USERS_ROLES_ENABLED=false\n";
        file_put_contents($path, $contents);
        chmod($path, 0600);
        $this->mock(RolesSetup::class)->shouldReceive('configure')->once()->andReturn(['rolesEnabled' => true]);
        $files = \Mockery::mock(Filesystem::class)->makePartial();
        $temporary = \Mockery::on(fn ($name) => str_starts_with($name, realpath($this->directory).'/.laravelusers-env-'));
        $files->shouldReceive('put')->once()->with($temporary, \Mockery::type('string'))->andReturnUsing(function ($name, $updated): int {
            clearstatcache(true, $name);
            $this->assertSame(0600, fileperms($name) & 0777);

            return file_put_contents($name, $updated);
        });
        $files->shouldReceive('move')->once()->with($temporary, realpath($path))->andReturn(false);
        $this->app->instance(Filesystem::class, $files);

        $this->artisan('laravelusers:update', ['--roles' => 'spatie', '--no-interaction' => true])
            ->expectsOutput('Unable to update the environment file. Check its file and directory permissions, then retry.')
            ->assertExitCode(1);

        clearstatcache(true, $path);
        $this->assertSame($contents, file_get_contents($path));
        $this->assertSame(0600, fileperms($path) & 0777);
        $this->assertSame([], glob($this->directory.'/.laravelusers-env-*'));
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
    }

    public function test_default_and_keep_leave_all_environment_values_unchanged(): void
    {
        $path = $this->app->environmentFilePath();
        $contents = "APP_NAME='Preserved host'\nLARAVEL_USERS_ROLES_ENABLED=false\nLARAVEL_USERS_ROLE_MODEL='Host\\OldRole'\n";
        file_put_contents($path, $contents);

        foreach ([[], ['--roles' => 'keep']] as $options) {
            $this->artisan('laravelusers:update', $options + ['--no-interaction' => true])->assertExitCode(0);
            $this->assertSame($contents, file_get_contents($path));
        }
        $this->artisan('laravelusers:update', ['--roles' => 'none', '--no-interaction' => true])->assertExitCode(0);
        $values = Dotenv::parse(file_get_contents($path));
        $this->assertSame('false', $values['LARAVEL_USERS_ROLES_ENABLED']);
        $this->assertSame('Host\\OldRole', $values['LARAVEL_USERS_ROLE_MODEL']);
        $this->assertSame('Preserved host', $values['APP_NAME']);
    }

    public function test_selected_middleware_list_removes_the_scalar_override_and_preserves_its_array(): void
    {
        $path = $this->app->environmentFilePath();
        file_put_contents($path, "APP_NAME='Preserved host'\nLARAVEL_USERS_ROLES_MIDDLWARE='old-role:admin'\n");
        $settings = ['rolesEnabled' => true, 'roleModel' => 'Host\\SelectedRole', 'rolesMiddlwareEnabled' => true, 'rolesMiddlware' => ['role:Administrator', 'can:manage users']];
        $this->mock(RolesSetup::class)->shouldReceive('configure')->once()->andReturn($settings);

        $this->artisan('laravelusers:update', ['--roles' => 'spatie', '--no-interaction' => true])->assertExitCode(0);

        $values = Dotenv::parse(file_get_contents($path));
        $this->assertArrayNotHasKey('LARAVEL_USERS_ROLES_MIDDLWARE', $values);
        $this->assertSame($settings['rolesMiddlware'], (require config_path('laravelusers-roles.php'))['rolesMiddlware']);
        $this->assertSame('Preserved host', $values['APP_NAME']);
    }
}
