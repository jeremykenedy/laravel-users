<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\File;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Test\TestCase;

class ComposerReadinessTest extends TestCase
{
    private string $directory;

    private string|false $originalPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/laravelusers-composer-readiness-'.bin2hex(random_bytes(8));
        $this->app->setBasePath($this->directory);
        foreach (['bin', 'vendor/composer', 'bootstrap/cache'] as $path) {
            File::ensureDirectoryExists(base_path($path));
        }
        File::put(base_path('bin/composer'), "#!/bin/sh\nexit 1\n");
        chmod(base_path('bin/composer'), 0755);
        File::put(base_path('composer.json'), json_encode(['name' => 'example/application', 'require' => new \stdClass()]));
        File::put(base_path('vendor/composer/installed.json'), json_encode(['packages' => [['name' => 'example/package', 'version' => '1.0.0']]]));
        File::put(base_path('artisan'), '<?php');
        File::put(base_path('composer.lock'), '{}');
        $this->originalPath = getenv('PATH');
        putenv('PATH='.base_path('bin'));
    }

    protected function tearDown(): void
    {
        putenv($this->originalPath === false ? 'PATH' : 'PATH='.$this->originalPath);
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_valid_host_files_and_supported_installed_metadata_are_ready(): void
    {
        $this->assertNull((new ComposerPackages())->readiness());
        File::put(base_path('vendor/composer/installed.json'), json_encode([['name' => 'example/package', 'version' => '1.0.0']]));
        $this->assertNull((new ComposerPackages())->readiness());
        File::delete(base_path('composer.lock'));
        $this->assertNull((new ComposerPackages())->readiness());
    }

    public function test_missing_composer_is_reported_before_any_application_files_are_changed(): void
    {
        File::delete(base_path('bin/composer'));
        $manifest = File::get(base_path('composer.json'));
        $installed = File::get(base_path('vendor/composer/installed.json'));

        $this->assertSame('laravelusers::ui.package_composer_missing', (new ComposerPackages())->readiness());
        $this->assertSame($manifest, File::get(base_path('composer.json')));
        $this->assertSame($installed, File::get(base_path('vendor/composer/installed.json')));
    }

    public function test_missing_or_invalid_manifest_is_not_ready(): void
    {
        File::delete(base_path('composer.json'));
        $this->assertSame('laravelusers::ui.package_composer_manifest', (new ComposerPackages())->readiness());
        foreach (['{broken', 'null', '"application"', '[]'] as $contents) {
            File::put(base_path('composer.json'), $contents);
            $this->assertSame('laravelusers::ui.package_composer_manifest', (new ComposerPackages())->readiness(), $contents);
        }
    }

    public function test_missing_or_malformed_installed_metadata_is_not_ready(): void
    {
        File::delete(base_path('vendor/composer/installed.json'));
        $this->assertSame('laravelusers::ui.package_composer_vendor', (new ComposerPackages())->readiness());
        foreach (['{broken', 'null', '{"packages":null}', '{"packages":[{"version":"1.0.0"}]}', '{"packages":[{"name":123}]}'] as $contents) {
            File::put(base_path('vendor/composer/installed.json'), $contents);
            $this->assertSame('laravelusers::ui.package_composer_vendor', (new ComposerPackages())->readiness(), $contents);
        }
    }

    public function test_missing_vendor_directory_is_not_ready(): void
    {
        File::deleteDirectory(base_path('vendor'));

        $this->assertSame('laravelusers::ui.package_composer_vendor', (new ComposerPackages())->readiness());
    }

    public function test_missing_artisan_or_bootstrap_cache_is_not_ready(): void
    {
        File::delete(base_path('artisan'));
        $this->assertSame('laravelusers::ui.package_composer_application', (new ComposerPackages())->readiness());
        File::put(base_path('artisan'), '<?php');
        File::deleteDirectory(base_path('bootstrap/cache'));
        $this->assertSame('laravelusers::ui.package_composer_application', (new ComposerPackages())->readiness());
    }

    public function test_artisan_must_be_a_regular_file(): void
    {
        File::delete(base_path('artisan'));
        File::ensureDirectoryExists(base_path('artisan'));

        $this->assertSame('laravelusers::ui.package_composer_application', (new ComposerPackages())->readiness());
    }

    public function test_an_existing_composer_lock_must_be_a_regular_file(): void
    {
        File::delete(base_path('composer.lock'));
        File::ensureDirectoryExists(base_path('composer.lock'));

        $this->assertSame('laravelusers::ui.package_composer_application', (new ComposerPackages())->readiness());
    }

    public function test_unwritable_installation_paths_are_not_ready(): void
    {
        foreach (['composer.json' => 'manifest', 'vendor' => 'vendor', 'bootstrap/cache' => 'application', 'composer.lock' => 'application'] as $path => $failure) {
            $absolute = base_path($path);
            $mode = fileperms($absolute) & 0777;
            chmod($absolute, is_dir($absolute) ? 0555 : 0444);
            clearstatcache(true, $absolute);

            try {
                if (is_writable($absolute)) {
                    $this->markTestSkipped('The process bypasses filesystem write permissions.');
                }
                $this->assertSame('laravelusers::ui.package_composer_'.$failure, (new ComposerPackages())->readiness(), $path);
            } finally {
                chmod($absolute, $mode);
                clearstatcache(true, $absolute);
            }
        }
    }
}
