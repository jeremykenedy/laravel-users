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
        $this->mock(RolesSetup::class)->shouldReceive('configure')->once()->andReturn($settings);

        $this->artisan('laravelusers:update', ['--roles' => 'spatie', '--no-interaction' => true])->assertExitCode(0);

        $values = Dotenv::parse(file_get_contents($path));
        $this->assertSame('true', $values['LARAVEL_USERS_ROLES_ENABLED']);
        $this->assertSame($settings['roleModel'], $values['LARAVEL_USERS_ROLE_MODEL']);
        $this->assertSame('true', $values['LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED']);
        $this->assertSame($settings['rolesMiddlware'], $values['LARAVEL_USERS_ROLES_MIDDLWARE']);
        $this->assertSame('Preserved host', $values['APP_NAME']);
        $this->assertSame(0600, fileperms($path) & 0777);
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
