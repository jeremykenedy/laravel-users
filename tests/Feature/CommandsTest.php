<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use jeremykenedy\laravelusers\LaravelUsersServiceProvider;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\LocalAvatars;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\RolesSetup;
use jeremykenedy\laravelusers\Test\TestCase;

class CommandsTest extends TestCase
{
    private string $directory;

    public function test_avatar_choices_preserve_host_configuration_and_keep_local_generation_by_default(): void
    {
        config(['laravelusers-avatar' => ['custom' => 'kept']]);
        $this->artisan('laravelusers:switch', ['--avatar' => 'ui-avatars'])->assertExitCode(0);
        $this->assertSame(['custom' => 'kept', 'source' => 'ui-avatars'], require config_path('laravelusers-avatar.php'));
        $this->assertDirectoryDoesNotExist(database_path('migrations'));
        $this->artisan('laravelusers:update', ['--avatar' => 'keep', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('ui-avatars', (require config_path('laravelusers-avatar.php'))['source']);
    }

    public function test_optional_installation_failures_do_not_write_frontend_or_avatar_settings(): void
    {
        if (LocalAvatars::diceBearInstalled() || PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('This check requires the optional library to be absent and PHP 8.2 or newer.');
        }
        $this->mock(ComposerPackages::class)->shouldReceive('installMany')->once()->with(['dicebear/core:^10.7', 'dicebear/styles:^10.6'], \Mockery::type('callable'))->andReturn(false);
        $this->artisan('laravelusers:update', ['--avatar' => 'dicebear', '--install-avatars' => true, '--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->assertFileDoesNotExist(config_path('laravelusers-avatar.php'));
    }

    public function test_toast_removal_is_explicit_and_preserves_published_host_files(): void
    {
        $files = new Filesystem();
        $path = config_path('toast.php');
        $files->ensureDirectoryExists(config_path());
        $files->put($path, 'host settings');
        $this->mock(ComposerPackages::class)->shouldReceive('remove')->once()->with('jeremykenedy/laravel-toast', \Mockery::type('callable'))->andReturn(true);
        $this->artisan('laravelusers:update', ['--toast' => 'remove', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('alert', (require config_path('laravelusers-notifications.php'))['driver']);
        $this->assertSame('host settings', $files->get($path));
        $this->assertDirectoryDoesNotExist(database_path('migrations'));
    }

    public function test_failed_toast_removal_preserves_existing_notification_and_frontend_settings(): void
    {
        $this->mock(ComposerPackages::class)->shouldReceive('remove')->once()->andReturn(false);
        $this->artisan('laravelusers:update', ['--toast' => 'remove', '--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-notifications.php'));
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
    }

    public function test_invalid_avatar_and_notification_options_are_rejected_before_writing(): void
    {
        foreach ([['--avatar' => 'unknown'], ['--install-avatars' => true], ['--toast' => 'unknown'], ['--notifications' => 'unknown'], ['--toast' => 'remove', '--notifications' => 'toast']] as $options) {
            $this->artisan('laravelusers:update', $options + ['--no-interaction' => true])->assertExitCode(1);
        }
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        (new Filesystem())->ensureDirectoryExists($this->directory.'/storage/framework/views');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_noninteractive_install_preserves_legacy_default_and_does_not_publish_views(): void
    {
        $this->artisan('laravelusers:install', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertFileExists(config_path('laravelusers.php'));
        $this->assertSame(['framework' => 'bootstrap4', 'theme' => 'light'], require config_path('laravelusers-ui.php'));
        $this->assertDirectoryDoesNotExist(resource_path('views/vendor/laravelusers'));
        $this->assertDirectoryDoesNotExist(database_path('migrations'));
    }

    public function test_account_setup_only_migrates_optional_storage_and_does_not_enable_features(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(database_path('migrations'));
        $hostMigration = database_path('migrations/2020_01_01_000000_host_migration.php');
        $files->put($hostMigration, '<?php throw new \\RuntimeException("Host migrations must not run during account setup.");');
        $columns = Schema::getColumnListing('users');
        $this->artisan('laravelusers:setup-accounts', ['--migrate' => true, '--no-interaction' => true])->assertExitCode(0);
        foreach (['laravelusers_account_preferences', 'laravelusers_email_changes', 'laravelusers_avatar_preferences', 'laravelusers_appearance_preferences'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }
        $this->assertTrue(Schema::hasColumn('laravelusers_appearance_preferences', 'gradient_strength'));
        $this->assertTrue(Schema::hasColumn('laravelusers_appearance_preferences', 'dark_gradient_strength'));
        $this->assertSame($columns, Schema::getColumnListing('users'));
        $this->assertFalse(config('laravelusers.account.enabled'));
        $this->assertFalse(config('laravelusers.avatar.per_user'));
        $this->assertSame('<?php throw new \\RuntimeException("Host migrations must not run during account setup.");', $files->get($hostMigration));
        $this->artisan('laravelusers:setup-accounts', ['--migrate' => true, '--no-interaction' => true])->assertExitCode(0);
    }

    public function test_hyphenated_command_aliases_preserve_the_existing_command_names(): void
    {
        $this->artisan('laravel-users:install', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('bootstrap4', (require config_path('laravelusers-ui.php'))['framework']);

        $this->artisan('laravel-users:update', ['--theme' => 'dark', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('dark', (require config_path('laravelusers-ui.php'))['theme']);

        $this->artisan('laravel-users:switch', ['--framework' => 'tailwind', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('tailwind', (require config_path('laravelusers-ui.php'))['framework']);
    }

    public function test_standalone_publish_alias_exports_package_files_without_replacing_host_configuration(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(config_path());
        $files->put(config_path('laravelusers.php'), '<?php return ["host" => true];');

        $this->artisan('laravel-users:publish', ['--no-interaction' => true])->assertExitCode(0);

        $this->assertSame('<?php return ["host" => true];', $files->get(config_path('laravelusers.php')));
        $publishPaths = ServiceProvider::pathsToPublish(LaravelUsersServiceProvider::class, 'laravelusers');
        $this->assertCount(3, $publishPaths);
        $this->assertContains(dirname(__DIR__, 2).'/src/resources/views', array_keys($publishPaths));
    }

    public function test_switch_preserves_custom_config_and_existing_view_files(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(config_path());
        $files->put(config_path('laravelusers.php'), '<?php return ["authEnabled" => false];');
        $path = resource_path('views/vendor/laravelusers/usersmanagement/show-users.blade.php');
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, 'Customized view');
        $this->artisan('laravelusers:update', ['--framework' => 'tailwind', '--theme' => 'system', '--views' => 'publish', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('Customized view', $files->get($path));
        $this->assertSame('<?php return ["authEnabled" => false];', $files->get(config_path('laravelusers.php')));
        $this->assertSame('tailwind', (require config_path('laravelusers-ui.php'))['framework']);
        $this->assertFileExists(resource_path('views/vendor/laravelusers/modern/show-users.blade.php'));
        $this->assertFileExists(resource_path('views/vendor/laravelusers/emails/welcome.blade.php'));
    }

    public function test_forced_view_update_keeps_a_complete_backup(): void
    {
        $files = new Filesystem();
        $path = resource_path('views/vendor/laravelusers/modern/show-users.blade.php');
        $files->ensureDirectoryExists(dirname($path));
        $files->put($path, 'Customized view');
        $this->artisan('laravelusers:update', ['--views' => 'publish', '--force' => true, '--no-interaction' => true])->assertExitCode(0);
        $backups = $files->directories(storage_path('app/laravelusers/backups'));
        $this->assertCount(1, $backups);
        $this->assertSame('Customized view', $files->get($backups[0].'/modern/show-users.blade.php'));
        $this->assertStringContainsString('@extends', $files->get($path));
    }

    public function test_invalid_input_writes_nothing(): void
    {
        foreach ([['--framework' => 'wrong'], ['--theme' => 'wrong'], ['--views' => 'wrong'], ['--with' => ['wrong']], ['--force' => true]] as $options) {
            $this->artisan('laravelusers:install', array_merge($options, ['--no-interaction' => true]))->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        }
    }

    public function test_update_retains_selected_framework_and_other_ui_settings(): void
    {
        config(['laravelusers-ui' => ['framework' => 'bootstrap5', 'theme' => 'dark', 'custom' => 'preserved']]);
        $this->artisan('laravelusers:update', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertSame(['framework' => 'bootstrap5', 'theme' => 'dark', 'custom' => 'preserved'], require config_path('laravelusers-ui.php'));
    }

    public function test_optional_integrations_only_print_instructions(): void
    {
        $this->artisan('laravelusers:install', ['--with' => ['ui-kit', 'toast', 'darkmode-toggle', 'ip-capture', 'seedster'], '--no-interaction' => true])
            ->expectsOutput('Optional setup: composer require jeremykenedy/laravel-ui-kit')
            ->expectsOutput('Then: php artisan ui-kit:install')
            ->assertExitCode(0);
        $this->assertSame(['framework' => 'bootstrap4', 'theme' => 'light'], require config_path('laravelusers-ui.php'));
    }

    public function test_cached_config_is_rejected_before_writing(): void
    {
        $files = new Filesystem();
        $cached = $this->app->getCachedConfigPath();
        $files->ensureDirectoryExists(dirname($cached));
        $files->put($cached, '<?php return [];');
        $this->app->forgetInstance('config_loaded_from_cache');

        try {
            $this->artisan('laravelusers:update', ['--framework' => 'tailwind', '--no-interaction' => true])->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        } finally {
            $files->delete($cached);
        }
    }

    public function test_interactive_choices_are_saved(): void
    {
        $this->artisan('laravelusers:install')
            ->expectsChoice('CSS framework', 'bootstrap5', ['bootstrap4', 'bootstrap5', 'tailwind'])
            ->expectsChoice('Color theme', 'dark', ['light', 'dark', 'system'])
            ->expectsChoice('Views (existing overrides always take precedence)', 'package', ['package', 'publish'])
            ->expectsChoice('Roles package (keep preserves existing or custom integrations)', 'keep', ['keep', 'none', 'laravel-roles', 'spatie'])
            ->expectsChoice('Avatar source (keep preserves current settings)', 'keep', array_merge(['keep'], Avatar::SOURCES))
            ->expectsChoice('Laravel Toast integration', 'keep', ['keep', 'install', 'remove'])
            ->assertExitCode(0);
        $this->assertSame(['framework' => 'bootstrap5', 'theme' => 'dark'], require config_path('laravelusers-ui.php'));
    }

    public function test_quick_switch_and_css_alias_preserve_configuration(): void
    {
        $this->artisan('laravelusers:switch', ['--css' => 'bootstrap5', '--frontend' => 'blade'])->assertExitCode(0);
        $this->assertSame('bootstrap5', (require config_path('laravelusers-ui.php'))['framework']);
        $before = file_get_contents(config_path('laravelusers.php'));
        $this->artisan('laravelusers:switch', ['--framework' => 'tailwind'])->assertExitCode(0);
        $this->assertSame($before, file_get_contents(config_path('laravelusers.php')));
        $this->assertSame('tailwind', (require config_path('laravelusers-ui.php'))['framework']);
    }

    public function test_conflicting_frameworks_and_unsupported_frontends_write_nothing(): void
    {
        $this->artisan('laravelusers:install', ['--css' => 'tailwind', '--framework' => 'bootstrap5', '--no-interaction' => true])->assertExitCode(1);
        $this->artisan('laravelusers:update', ['--frontend' => 'vue', '--no-interaction' => true])->assertExitCode(1);
        $this->artisan('laravelusers:switch')->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
    }

    public function test_generated_frontend_settings_keep_environment_fallbacks(): void
    {
        $this->artisan('laravelusers:install', ['--framework' => 'tailwind', '--theme' => 'dark', '--no-interaction' => true])->assertExitCode(0);
        $contents = file_get_contents(config_path('laravelusers-ui.php'));
        $this->assertStringContainsString("env('LARAVEL_USERS_FRONTEND', 'tailwind')", $contents);
        $this->assertStringContainsString("env('LARAVEL_USERS_THEME', 'dark')", $contents);
        $settings = require config_path('laravelusers-ui.php');
        $this->assertSame('tailwind', $settings['framework']);
        $this->assertSame('dark', $settings['theme']);
    }

    public function test_roles_are_preserved_by_default_and_can_be_explicitly_disabled(): void
    {
        config(['laravelusers.rolesEnabled' => true, 'laravelusers.roleModel' => 'Host\\Role', 'laravelusers.middleware' => ['can:manage-users']]);
        $this->artisan('laravelusers:update', ['--roles' => 'keep', '--no-interaction' => true])->assertExitCode(0);
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
        $this->artisan('laravelusers:switch', ['--roles' => 'none'])->assertExitCode(0);
        $this->assertSame(['rolesEnabled' => false], require config_path('laravelusers-roles.php'));
        $this->assertSame(['can:manage-users'], config('laravelusers.middleware'));
        $this->assertStringContainsString("env('LARAVEL_USERS_ROLES_ENABLED', false)", file_get_contents(config_path('laravelusers-roles.php')));
    }

    public function test_invalid_role_options_do_not_write_files_or_run_composer(): void
    {
        $composer = $this->mock(ComposerPackages::class);
        $composer->shouldNotReceive('install');
        foreach ([['--roles' => 'wrong'], ['--install-roles' => true], ['--roles' => 'keep', '--role-middleware' => 'role:admin'], ['--roles' => 'spatie', '--role-middleware' => '']] as $options) {
            $this->artisan('laravelusers:update', array_merge($options, ['--no-interaction' => true]))->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('laravelusers.php'));
            $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
        }
    }

    public function test_role_selection_writes_separate_config_and_preserves_main_config(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(config_path());
        $files->put(config_path('laravelusers.php'), '<?php return ["custom" => "preserved"];');
        $setup = $this->mock(RolesSetup::class);
        $settings = ['rolesEnabled' => true, 'roleModel' => 'Host\\Role', 'rolesMiddlwareEnabled' => true, 'rolesMiddlware' => ['role:admin', 'permission:manage users']];
        $setup->shouldReceive('configure')->once()->andReturn($settings);
        $this->artisan('laravelusers:update', ['--roles' => 'spatie', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame($settings, require config_path('laravelusers-roles.php'));
        $this->assertSame('<?php return ["custom" => "preserved"];', $files->get(config_path('laravelusers.php')));
    }

    public function test_missing_role_package_only_prints_instructions_without_opt_in(): void
    {
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $this->markTestSkipped('This check requires the optional package to be absent.');
        }
        $this->mock(ComposerPackages::class)->shouldNotReceive('install');
        $this->artisan('laravelusers:install', ['--roles' => 'spatie', '--no-interaction' => true])
            ->expectsOutput('Run: composer require spatie/laravel-permission')
            ->assertExitCode(0);
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
    }

    public function test_installer_cannot_install_a_second_roles_package(): void
    {
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $this->markTestSkipped('This check requires the selected package to be absent.');
        }
        $this->mock(ManagedPackages::class)->shouldReceive('installed')->once()->with('laravel-roles')->andReturn(true);
        $this->mock(ComposerPackages::class)->shouldNotReceive('install');
        $this->artisan('laravelusers:install', ['--roles' => 'spatie', '--install-roles' => true, '--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
    }

    public function test_role_dependency_install_failure_preserves_application_files(): void
    {
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $this->markTestSkipped('This check requires the optional package to be absent.');
        }
        $this->mock(ComposerPackages::class)->shouldReceive('install')->once()->with('spatie/laravel-permission', \Mockery::type('callable'))->andReturn(false);
        $this->artisan('laravelusers:install', ['--roles' => 'spatie', '--install-roles' => true, '--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers.php'));
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
    }

    public function test_successful_dependency_install_still_requires_host_model_setup(): void
    {
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $this->markTestSkipped('This check requires the optional package to be absent.');
        }
        $this->mock(ComposerPackages::class)->shouldReceive('install')->once()->with('spatie/laravel-permission', \Mockery::type('callable'))->andReturn(true);
        $this->artisan('laravelusers:install', ['--roles' => 'spatie', '--install-roles' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertFileDoesNotExist(config_path('laravelusers-roles.php'));
        $this->assertDirectoryDoesNotExist(database_path('migrations'));
    }
}
