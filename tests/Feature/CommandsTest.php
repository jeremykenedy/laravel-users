<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Test\TestCase;

class CommandsTest extends TestCase
{
    private string $directory;

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
            ->expectsChoice('Frontend framework', 'bootstrap5', ['bootstrap4', 'bootstrap5', 'tailwind'])
            ->expectsChoice('Color theme', 'dark', ['light', 'dark', 'system'])
            ->expectsChoice('Views (existing overrides always take precedence)', 'package', ['package', 'publish'])
            ->assertExitCode(0);
        $this->assertSame(['framework' => 'bootstrap5', 'theme' => 'dark'], require config_path('laravelusers-ui.php'));
    }
}
