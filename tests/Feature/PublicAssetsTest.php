<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Support\PublicAssets;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

class PublicAssetsTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-assets-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        $this->app->useStoragePath($this->directory.'/storage');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_install_update_and_publish_export_real_assets_and_preserve_custom_files(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(public_path('vendor/laravelusers'));
        $files->put(public_path('vendor/laravelusers/custom.css'), 'custom styles');
        foreach (['laravel-users:install', 'laravel-users:update', 'laravel-users:publish'] as $command) {
            $this->artisan($command, ['--no-interaction' => true])->assertExitCode(0);
            $manifest = json_decode($files->get(public_path('vendor/laravelusers/manifest.json')), true);
            foreach (['modern.css', 'tailwind.css', 'users.js', 'runtime-vue.js', 'runtime-react.js', 'runtime-svelte.js', 'runtime-livewire.js', 'runtime-vue.licenses.json', 'runtime-react.licenses.json', 'runtime-svelte.licenses.json', 'runtime-livewire.licenses.json'] as $name) {
                $path = public_path('vendor/laravelusers/'.$manifest['files'][$name]['path']);
                $this->assertSame(hash('sha256', PublicAssets::contents($name)), hash_file('sha256', $path));
                $this->assertStringContainsString('/vendor/laravelusers/releases/', PublicAssets::url($name));
            }
            $this->assertSame('custom styles', $files->get(public_path('vendor/laravelusers/custom.css')));
        }
        $this->assertCount(1, $files->directories(public_path('vendor/laravelusers/releases')));
    }

    public function test_staging_failure_preserves_the_active_manifest_and_assets(): void
    {
        $real = new Filesystem();
        (new PublicAssets($real))->publish();
        $original = $real->get(public_path('vendor/laravelusers/manifest.json'));
        $files = \Mockery::mock(Filesystem::class)->makePartial();
        $files->shouldReceive('replace')->andReturnUsing(function ($path, $content, $mode) use ($real) {
            if (str_contains($path, '/.stage-') && str_ends_with($path, '/users.js')) {
                throw new RuntimeException('Disk write failed');
            }
            $real->replace($path, $content, $mode);
        });

        try {
            (new PublicAssets($files))->publish();
            $this->fail('The failed disk write should stop publication.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Disk write failed', $exception->getMessage());
        }
        $this->assertSame($original, $real->get(public_path('vendor/laravelusers/manifest.json')));
        $this->assertNotNull(PublicAssets::url('users.js'));
        $this->assertSame([], glob(public_path('vendor/laravelusers/.stage-*')));
    }

    public function test_missing_or_stale_manifests_use_the_bundled_fallback(): void
    {
        $this->assertNull(PublicAssets::url('users.js'));
        $files = new Filesystem();
        (new PublicAssets($files))->publish();
        $manifest = json_decode($files->get(public_path('vendor/laravelusers/manifest.json')), true);
        $manifest['files']['users.js']['sha256'] = str_repeat('0', 64);
        $files->put(public_path('vendor/laravelusers/manifest.json'), json_encode($manifest));
        $this->assertNull(PublicAssets::url('users.js'));
        $files->put(public_path('vendor/laravelusers/manifest.json'), 'broken json');
        $this->assertNull(PublicAssets::url('modern.css'));
        $this->assertStringContainsString('function buttonLabels()', PublicAssets::contents('users.js'));
    }

    public function test_asset_names_cannot_escape_the_package_directory(): void
    {
        $this->expectException(RuntimeException::class);
        PublicAssets::contents('../../config/laravelusers.php');
    }

    public function test_module_assets_render_once_for_inline_and_published_delivery(): void
    {
        $inline = view('laravelusers::partials.asset', ['name' => 'material3.js', 'module' => true])->render();
        $this->assertStringContainsString('type="module" data-navigate-once', $inline);
        $this->assertStringContainsString(PublicAssets::contents('material3.js'), $inline);

        (new PublicAssets(new Filesystem()))->publish();
        $published = view('laravelusers::partials.asset', ['name' => 'material3.js', 'module' => true])->render();
        $this->assertStringContainsString('type="module" data-navigate-once', $published);
        $this->assertStringContainsString('src="'.PublicAssets::url('material3.js').'"', $published);
        $classic = view('laravelusers::partials.asset', ['name' => 'users.js'])->render();
        $this->assertStringNotContainsString('type="module"', $classic);
        $this->assertStringNotContainsString('data-navigate-once', $classic);
    }

    public function test_symbolic_link_destinations_are_rejected(): void
    {
        $files = new Filesystem();
        $files->ensureDirectoryExists(public_path('vendor'));
        $files->ensureDirectoryExists($this->directory.'/host-assets');
        symlink($this->directory.'/host-assets', public_path('vendor/laravelusers'));
        $this->expectException(RuntimeException::class);
        (new PublicAssets($files))->publish();
    }
}
