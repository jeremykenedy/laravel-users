<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\App\Http\Middleware\VerifyImpersonationState;
use jeremykenedy\laravelusers\Support\HostRouting;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

class HostRoutingTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-routes-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
        (new Filesystem())->ensureDirectoryExists(base_path('routes'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_install_and_update_add_middleware_idempotently_without_replacing_custom_routes(): void
    {
        $source = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route as Web;

// Host dashboard routes.
Web::middleware(['auth', 'verified'])->group(function () {
    Web::get('/dashboard', fn () => 'dashboard')->name('dashboard');
});
Web::group(['prefix' => 'reports', 'middleware' => ['auth']], function () {
    Web::get('/monthly', fn () => 'report');
});
Web::middleware(config('host.middleware', []))->group(function () {
    Web::get('/custom', fn () => 'custom');
});
PHP;
        file_put_contents(base_path('routes/web.php'), $source);
        $this->artisan('laravel-users:install', ['--no-interaction' => true])->assertExitCode(0);
        $updated = file_get_contents(base_path('routes/web.php'));
        $this->assertSame(6, substr_count($updated, VerifyImpersonationState::class));
        $this->assertStringContainsString('class_exists(\\'.VerifyImpersonationState::class.'::class)', $updated);
        $this->assertStringContainsString('// Host dashboard routes.', $updated);
        $this->assertStringContainsString("Web::get('/dashboard', fn () => 'dashboard')->name('dashboard');", $updated);
        $this->assertStringContainsString("Web::middleware(config('host.middleware', []))", $updated);
        $this->artisan('laravel-users:update', ['--no-interaction' => true])->assertExitCode(0);
        $this->assertSame($updated, file_get_contents(base_path('routes/web.php')));
    }

    public function test_invalid_host_php_is_rejected_before_any_installation_changes(): void
    {
        $source = '<?php Route::get(';
        file_put_contents(base_path('routes/web.php'), $source);
        $this->artisan('laravel-users:install', ['--no-interaction' => true])->assertExitCode(1);
        $this->assertSame($source, file_get_contents(base_path('routes/web.php')));
        $this->assertFileDoesNotExist(config_path('laravelusers-ui.php'));
        $this->assertFileDoesNotExist(public_path('vendor/laravelusers/manifest.json'));
    }

    public function test_routes_with_a_namespace_and_a_string_middleware_remain_valid(): void
    {
        file_put_contents(base_path('routes/web.php'), '<?php namespace Host; use Illuminate\Support\Facades\Route; Route::middleware("auth")->group(fn () => null);');
        $routing = $this->app->make(HostRouting::class);
        $routing->write($routing->prepare());
        $updated = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString('namespace Host;', $updated);
        $this->assertStringContainsString('"auth", ...', $updated);
        $this->assertSame($updated, $routing->prepare()['updated']);
    }

    public function test_concurrent_host_edits_are_preserved(): void
    {
        file_put_contents(base_path('routes/web.php'), '<?php use Illuminate\Support\Facades\Route; Route::get("/", fn () => "home");');
        $routing = $this->app->make(HostRouting::class);
        $plan = $routing->prepare();
        $custom = '<?php // updated by host developer';
        file_put_contents(base_path('routes/web.php'), $custom);

        try {
            $routing->write($plan);
            $this->fail('Concurrent route changes must stop publication.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('changed during setup', $exception->getMessage());
        }
        $this->assertSame($custom, file_get_contents(base_path('routes/web.php')));
    }
}
