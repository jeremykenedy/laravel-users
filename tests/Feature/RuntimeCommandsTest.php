<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Composer\InstalledVersions;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use jeremykenedy\laravelusers\App\Http\Middleware\VerifyImpersonationState;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\NativeRuntime;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\PublicAssets;
use jeremykenedy\laravelusers\Test\TestCase;
use Livewire\LivewireServiceProvider;
use Mockery;
use ReflectionProperty;

class RuntimeCommandsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-runtime-commands-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        (new Filesystem())->ensureDirectoryExists(storage_path('framework/views'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_blade_installs_with_released_bootstrap_choices_without_changing_host_build_files(): void
    {
        $this->installCombinations(['blade']);
    }

    public function test_future_runtime_is_rejected_even_when_its_provider_is_installed(): void
    {
        $this->registerLivewire();
        $this->assertTrue(InstalledVersions::isInstalled('livewire/livewire'));
        $this->assertTrue($this->app->bound('livewire'));
        $this->artisan('laravelusers:install', ['--frontend' => 'livewire', '--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
    }

    private function installCombinations(array $runtimes): void
    {
        $files = new Filesystem();
        $preserved = [
            'composer.json'                                                            => json_encode(['name' => 'host/application', 'require' => ['laravel/framework' => '^12.0', 'host/private-package' => '^1.0'], 'autoload' => ['psr-4' => ['App\\' => 'app/']]], JSON_PRETTY_PRINT),
            'package.json'                                                             => json_encode(['private' => true, 'scripts' => ['build' => 'custom-build'], 'dependencies' => ['vue' => '2.7.16', 'host-private-ui' => 'file:../private-ui']], JSON_PRETTY_PRINT),
            'package-lock.json'                                                        => '{"lockfileVersion":3,"host":"preserved"}',
            'vite.config.js'                                                           => 'export default { plugins: [hostPlugin()] };',
            'tailwind.config.js'                                                       => 'export default { content: ["./host/**/*.blade.php"] };',
            'resources/css/app.css'                                                    => '.host { color: purple; }',
            'resources/js/app.js'                                                      => 'import "./host-app";',
            'resources/views/vendor/laravelusers/usersmanagement/show-users.blade.php' => 'Host user listing',
            'config/laravelusers.php'                                                  => '<?php return ["customView" => "host.users"];',
        ];
        foreach ($preserved as $path => $contents) {
            $files->ensureDirectoryExists(dirname(base_path($path)));
            $files->put(base_path($path), $contents);
        }
        $files->ensureDirectoryExists(base_path('routes'));
        $files->put(base_path('routes/web.php'), '<?php use Illuminate\Support\Facades\Route; Route::middleware("auth")->group(function () { Route::get("/host", fn () => "host page"); });');
        $updatedRoutes = null;
        $this->mock(ComposerPackages::class)->shouldNotReceive('install', 'installMany', 'remove', 'setup');

        foreach ($runtimes as $runtime) {
            foreach (Frontend::RELEASE_FRAMEWORKS as $framework) {
                $this->artisan('laravelusers:install', ['--frontend' => $runtime, '--framework' => $framework, '--views' => 'publish', '--no-interaction' => true])->assertExitCode(0);
                $settings = require config_path('laravelusers-ui.php');
                $this->assertSame(['framework' => $framework, 'theme' => 'light', 'runtime' => $runtime], $settings);
                $this->assertStringContainsString("env('LARAVEL_USERS_RUNTIME', '".$runtime."')", $files->get(config_path('laravelusers-ui.php')));
                foreach ($preserved as $path => $contents) {
                    $this->assertSame($contents, $files->get(base_path($path)), $runtime.'/'.$framework.': '.$path);
                }
                $routes = $files->get(base_path('routes/web.php'));
                $this->assertStringContainsString(VerifyImpersonationState::class, $routes);
                $this->assertStringContainsString('Route::get("/host", fn () => "host page")', $routes);
                $updatedRoutes = $updatedRoutes ?? $routes;
                $this->assertSame($updatedRoutes, $routes);
                $asset = $runtime === 'blade' ? 'users.js' : 'runtime-'.$runtime.'.js';
                $this->assertNotNull(PublicAssets::url($asset));
                $manifest = json_decode($files->get(public_path('vendor/laravelusers/manifest.json')), true);
                $entry = $manifest['files'][$asset];
                $this->assertSame(hash('sha256', PublicAssets::contents($asset)), hash_file('sha256', public_path('vendor/laravelusers/'.$entry['path'])));
                $this->assertSame([], glob(public_path('vendor/laravelusers/.stage-*')));
                $this->assertDirectoryDoesNotExist(base_path('node_modules'));
            }
        }
    }

    public function test_update_retains_runtime_and_custom_settings_and_republishes_valid_assets(): void
    {
        $settings = ['framework' => 'bootstrap5', 'theme' => 'dark', 'runtime' => 'blade', 'host_setting' => ['retained' => true]];
        config(['laravelusers-ui' => $settings]);
        $this->artisan('laravel-users:update', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertSame($settings, require config_path('laravelusers-ui.php'));
        $manifest = json_decode(file_get_contents(public_path('vendor/laravelusers/manifest.json')), true);
        file_put_contents(public_path('vendor/laravelusers/'.$manifest['files']['users.js']['path']), 'damaged');
        $this->assertNull(PublicAssets::url('users.js'));
        $this->artisan('laravelusers:update', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertNotNull(PublicAssets::url('users.js'));
        $this->assertSame($settings, require config_path('laravelusers-ui.php'));
    }

    public function test_frontend_only_switch_preserves_css_theme_and_other_settings(): void
    {
        config(['laravelusers-ui' => ['framework' => 'bootstrap5', 'theme' => 'system', 'host_setting' => 'retained']]);
        $this->artisan('laravel-users:switch', ['--frontend' => 'blade'])->assertExitCode(0);
        $this->assertSame(['framework' => 'bootstrap5', 'theme' => 'system', 'host_setting' => 'retained', 'runtime' => 'blade'], require config_path('laravelusers-ui.php'));
        $this->assertNotNull(PublicAssets::url('users.js'));
    }

    public function test_configured_runtime_remains_active_without_adding_a_new_sidecar_key(): void
    {
        config(['laravelusers.runtime' => 'blade']);
        $this->artisan('laravelusers:update', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertSame(['framework' => 'bootstrap4', 'theme' => 'light'], require config_path('laravelusers-ui.php'));
        $this->assertSame('blade', NativeRuntime::name());
        $this->assertNotNull(PublicAssets::url('users.js'));
    }

    public function test_invalid_options_cannot_publish_assets_change_routes_or_configure_optional_packages(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(base_path('routes'));
        $source = '<?php // Host routes remain unchanged.';
        $files->put(base_path('routes/web.php'), $source);
        $this->mock(PackageRequirements::class)->shouldNotReceive('configure');
        $this->mock(ComposerPackages::class)->shouldNotReceive('install', 'installMany', 'remove', 'setup');
        foreach ([['--frontend' => 'unknown'], ['--frontend' => ''], ['--framework' => 'unknown'], ['--framework' => 'bulma', '--css' => 'tailwind'], ['--toast' => 'remove', '--notifications' => 'both']] as $options) {
            $this->artisan('laravelusers:install', $options + ['--setup-packages' => true, '--setup-integrations' => true, '--views' => 'publish', '--no-interaction' => true])->assertExitCode(1);
            $this->assertSame($source, $files->get(base_path('routes/web.php')));
            $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
            $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
            $this->assertDirectoryDoesNotExist(resource_path('views/vendor/laravelusers'));
        }
    }

    public function test_livewire_without_an_active_provider_is_rejected_before_any_setup(): void
    {
        $this->assertFalse($this->app->bound('livewire'));
        $this->mock(PackageRequirements::class)->shouldNotReceive('configure');
        $this->artisan('laravelusers:install', ['--frontend' => 'livewire', '--setup-packages' => true, '--no-interaction' => true])
            ->expectsOutput('This release supports --frontend=blade. Other runtimes will be added in later releases.')
            ->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
    }

    public function test_livewire_two_metadata_is_rejected_even_with_a_registered_provider(): void
    {
        $this->registerLivewire();
        $property = new ReflectionProperty(InstalledVersions::class, 'installedByVendor');
        InstalledVersions::getAllRawData();
        $original = $property->getValue();
        $modified = $original;
        foreach ($modified as &$dataset) {
            if (isset($dataset['versions']['livewire/livewire'])) {
                $dataset['versions']['livewire/livewire']['version'] = '2.12.8.0';
            }
        }
        unset($dataset);
        $property->setValue(null, $modified);

        try {
            $this->assertSame('2.12.8.0', InstalledVersions::getVersion('livewire/livewire'));
            $this->artisan('laravelusers:install', ['--frontend' => 'livewire', '--no-interaction' => true])->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
            $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
        } finally {
            $property->setValue(null, $original);
        }
    }

    public function test_deferred_frameworks_are_rejected_before_changing_host_files(): void
    {
        $this->mock(PackageRequirements::class)->shouldNotReceive('configure');
        $this->mock(ComposerPackages::class)->shouldNotReceive('install', 'installMany', 'remove', 'setup');
        foreach (['livewire', 'vue', 'react', 'svelte'] as $runtime) {
            $this->artisan('laravelusers:install', ['--frontend' => $runtime, '--setup-packages' => true, '--no-interaction' => true])->assertExitCode(1);
        }
        foreach (['tailwind', 'materialize', 'material3', 'bulma', 'foundation'] as $framework) {
            $this->artisan('laravelusers:install', ['--framework' => $framework, '--setup-packages' => true, '--no-interaction' => true])->assertExitCode(1);
        }
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
    }

    public function test_cached_configuration_prevents_runtime_switch_and_asset_publication(): void
    {
        $files = new Filesystem();
        $cached = $this->app->getCachedConfigPath();
        $files->ensureDirectoryExists(dirname($cached));
        $files->put($cached, '<?php return [];');
        $this->app->forgetInstance('config_loaded_from_cache');

        try {
            $this->artisan('laravelusers:switch', ['--frontend' => 'vue'])->assertExitCode(1);
            $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
            $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
        } finally {
            $files->delete($cached);
        }
    }

    public function test_toast_setup_runs_for_every_css_framework_and_preserves_published_settings(): void
    {
        $this->registerToast();
        foreach (Frontend::RELEASE_FRAMEWORKS as $framework) {
            (new Filesystem())->delete(config_path('toast.php'));
            $this->artisan('laravelusers:setup-package', ['package' => 'toast', '--framework' => $framework, '--no-interaction' => true])->assertExitCode(0);
            $this->assertFileExists(config_path('toast.php'));
            $this->assertSame(in_array($framework, ['bootstrap4', 'bootstrap5', 'tailwind'], true) ? $framework : 'bootstrap5', config('toast.css_framework'));
            $this->assertSame('blade', config('toast.frontend'));
            $this->assertSame('alert', config('laravelusers.notifications.driver'));
        }
        $custom = '<?php return ["position" => "bottom-left", "duration" => 10000, "host" => true];';
        file_put_contents(config_path('toast.php'), $custom);
        config(['toast.position' => 'bottom-left', 'toast.duration' => 10000]);
        $this->artisan('laravelusers:setup-package', ['package' => 'toast', '--framework' => 'bootstrap5', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame($custom, file_get_contents(config_path('toast.php')));
        $this->assertSame('bottom-left', config('toast.position'));
        $this->assertSame(10000, config('toast.duration'));
    }

    public function test_both_notifications_require_installed_toast_and_preserve_custom_configuration(): void
    {
        $this->artisan('laravelusers:update', ['--notifications' => 'both', '--setup-packages' => true, '--no-interaction' => true])->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->registerToast();
        (new Filesystem())->ensureDirectoryExists(config_path());
        $custom = '<?php return ["position" => "bottom-left", "host" => true];';
        file_put_contents(config_path('toast.php'), $custom);
        $this->mock(ComposerPackages::class)->shouldNotReceive('install', 'remove');
        $this->artisan('laravelusers:update', ['--toast' => 'install', '--notifications' => 'both', '--no-interaction' => true])->assertExitCode(0);
        $this->assertSame('both', (require config_path('laravelusers-notifications.php'))['driver']);
        $this->assertSame($custom, file_get_contents(config_path('toast.php')));
    }

    public function test_automatic_toast_setup_does_not_change_the_host_ui_kit_framework_or_runtime(): void
    {
        $this->registerToast();
        config(['ui-kit.css_framework' => 'tailwind', 'ui-kit.frontend' => 'vue', 'toast.css_framework' => null, 'toast.frontend' => null]);
        file_put_contents($this->app->environmentFilePath(), "UI_KIT_CSS=tailwind\nUI_KIT_FRONTEND=vue\nHOST_SETTING=kept\n");
        $this->app->instance('env', 'local');

        try {
            $this->artisan('laravelusers:setup-package', ['package' => 'toast', '--framework' => 'bootstrap5', '--no-interaction' => true])->assertExitCode(0);
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertSame('tailwind', config('ui-kit.css_framework'));
        $this->assertSame('vue', config('ui-kit.frontend'));
        $this->assertSame('bootstrap5', config('toast.css_framework'));
        $this->assertSame('blade', config('toast.frontend'));
        $this->assertSame("UI_KIT_CSS=tailwind\nUI_KIT_FRONTEND=vue\nHOST_SETTING=kept\nTOAST_CSS=bootstrap5\nTOAST_FRONTEND=blade\n", file_get_contents($this->app->environmentFilePath()));
    }

    public function test_explicit_toast_install_automatically_configures_it_once_before_enabling_both_notifications(): void
    {
        $this->registerToast();
        $composer = $this->mock(ComposerPackages::class);
        $composer->shouldNotReceive('install');
        $composer->shouldReceive('setup')->once()->with('toast', 'bootstrap5', false, Mockery::type('callable'))
            ->andReturnUsing(fn () => $this->app->make(Kernel::class)->call('laravelusers:setup-package', ['package' => 'toast', '--framework' => 'bootstrap5', '--no-interaction' => true]) === 0);
        $this->artisan('laravelusers:install', ['--toast' => 'install', '--notifications' => 'both', '--framework' => 'bootstrap5', '--setup-integrations' => true, '--no-interaction' => true])->assertExitCode(0);
        $this->assertFileExists(config_path('toast.php'));
        $this->assertSame('bootstrap5', config('toast.css_framework'));
        $this->assertSame('blade', config('toast.frontend'));
        $this->assertSame('both', (require config_path('laravelusers-notifications.php'))['driver']);
        $this->assertSame('bootstrap5', (require config_path('laravelusers-ui.php'))['framework']);
    }

    public function test_failed_automatic_toast_setup_does_not_enable_notifications_or_write_frontend_settings(): void
    {
        $this->registerToast();
        $this->mock(ComposerPackages::class)->shouldReceive('setup')->once()->with('toast', 'bootstrap4', false, Mockery::type('callable'))->andReturn(false);
        $this->mock(PackageRequirements::class)->shouldNotReceive('configure');
        $this->artisan('laravelusers:update', ['--toast' => 'install', '--notifications' => 'both', '--setup-packages' => true, '--no-interaction' => true])
            ->expectsOutput('Laravel Toast was installed, but setup failed. Existing notification settings were preserved.')
            ->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-notifications.php'));
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->assertDirectoryDoesNotExist(public_path('vendor/laravelusers'));
    }

    public function test_interactive_toast_removal_cannot_leave_both_notifications_enabled(): void
    {
        $this->registerToast();
        $this->mock(ComposerPackages::class)->shouldNotReceive('remove');
        $this->artisan('laravelusers:install', ['--frontend' => 'blade', '--framework' => 'bootstrap4', '--theme' => 'light', '--views' => 'package', '--roles' => 'keep', '--avatar' => 'keep', '--notifications' => 'both'])
            ->expectsChoice('Laravel Toast integration', 'remove', ['keep', 'install', 'remove'])
            ->expectsOutput('Toast removal requires --notifications=alert or no notification selection.')
            ->assertExitCode(1);
        $this->assertFileDoesNotExist(config_path('laravelusers-notifications.php'));
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
    }

    private function registerLivewire(): void
    {
        if (!class_exists(LivewireServiceProvider::class)) {
            $this->markTestSkipped('Run the optional Livewire integration job to verify the installed runtime.');
        }
        $this->app->register(LivewireServiceProvider::class);
    }

    private function registerToast(): void
    {
        if (!class_exists(ToastServiceProvider::class)) {
            $this->markTestSkipped('Run the optional Toast integration job to verify package setup.');
        }
        $this->app->register(ToastServiceProvider::class);
    }
}
