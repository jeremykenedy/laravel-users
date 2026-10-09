<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\File;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Test\TestCase;

class ComposerPackagesTest extends TestCase
{
    private string $originalBasePath;

    private string $originalPath;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalBasePath = base_path();
        $this->originalPath = (string) getenv('PATH');
        $this->fixturePath = sys_get_temp_dir().'/laravelusers-composer-tests-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->fixturePath.'/bin');
        File::ensureDirectoryExists($this->fixturePath.'/vendor/composer');
        File::ensureDirectoryExists($this->fixturePath.'/bootstrap/cache');
        File::put($this->fixturePath.'/composer.json', '{}');
        foreach (['packages.php', 'services.php', 'config.php'] as $file) {
            File::put($this->fixturePath.'/bootstrap/cache/'.$file, '<?php return [];');
        }
        File::put($this->fixturePath.'/bin/composer', '#!'.PHP_BINARY."\n".<<<'PHP'
<?php
file_put_contents('commands.jsonl', json_encode(array_slice($argv, 1))."\n", FILE_APPEND);
if (is_file('composer-fails')) {
    exit(1);
}
$packages = $argv[1] === 'require' || is_file('package-remains') ? [['name' => $argv[2]]] : [];
file_put_contents('vendor/composer/installed.json', is_file('manifest-payload') ? file_get_contents('manifest-payload') : json_encode(['packages' => $packages]));
PHP);
        chmod($this->fixturePath.'/bin/composer', 0700);
        File::put($this->fixturePath.'/artisan', <<<'PHP'
<?php
file_put_contents('commands.jsonl', json_encode(array_slice($argv, 1))."\n", FILE_APPEND);
exit(is_file('discovery-fails') && $argv[1] === 'package:discover' ? 1 : 0);
PHP);
        putenv('PATH='.$this->fixturePath.'/bin'.PATH_SEPARATOR.$this->originalPath);
        $this->app->useAppPath($this->fixturePath.'/app');
        $this->app->setBasePath($this->fixturePath);
    }

    protected function tearDown(): void
    {
        putenv('PATH='.$this->originalPath);
        $this->app->setBasePath($this->originalBasePath);
        File::deleteDirectory($this->fixturePath);
        parent::tearDown();
    }

    public function test_settings_changes_use_fixed_arguments_and_refresh_only_generated_manifests(): void
    {
        $composer = new ComposerPackages();
        $package = 'jeremykenedy/laravel-toast';
        $this->assertTrue($composer->changeFromSettings('install', $package));
        $this->assertFileDoesNotExist(base_path('bootstrap/cache/packages.php'));
        $this->assertFileDoesNotExist(base_path('bootstrap/cache/services.php'));
        $this->assertFileExists(base_path('bootstrap/cache/config.php'));
        $this->assertTrue($composer->changeFromSettings('remove', $package));
        $commands = array_map(fn ($line) => json_decode($line, true), file(base_path('commands.jsonl'), FILE_IGNORE_NEW_LINES));
        $this->assertSame([
            ['require', $package, '--no-interaction', '--no-scripts', '--no-plugins'],
            ['package:discover', '--no-interaction', '--no-ansi'],
            ['queue:restart', '--no-interaction', '--no-ansi'],
            ['remove', $package, '--no-interaction', '--no-scripts', '--no-plugins'],
            ['package:discover', '--no-interaction', '--no-ansi'],
            ['queue:restart', '--no-interaction', '--no-ansi'],
        ], $commands);
    }

    public function test_unapproved_packages_and_operations_never_start_a_process(): void
    {
        $composer = new ComposerPackages();
        $this->assertFalse($composer->changeFromSettings('install', 'unapproved/package'));
        $this->assertFalse($composer->changeFromSettings('update', 'jeremykenedy/laravel-toast'));
        $this->assertFileDoesNotExist(base_path('commands.jsonl'));
    }

    public function test_cli_install_remove_and_setup_use_separate_process_arguments(): void
    {
        $composer = new ComposerPackages();
        $output = fn ($text) => null;
        $this->assertTrue($composer->install('jeremykenedy/laravel-toast', $output));
        $this->assertTrue($composer->installMany(['dicebear/core:^10.7', 'dicebear/styles:^10.6'], $output));
        $this->assertTrue($composer->remove('jeremykenedy/laravel-toast', $output));
        $this->assertTrue($composer->setup('toast', 'bootstrap5', false, $output));
        $this->assertTrue($composer->setup('spatie', 'tailwind', true, $output));
        $commands = array_map(fn ($line) => json_decode($line, true), file(base_path('commands.jsonl'), FILE_IGNORE_NEW_LINES));
        $this->assertSame([
            ['require', 'jeremykenedy/laravel-toast', '--no-interaction'],
            ['require', 'dicebear/core:^10.7', 'dicebear/styles:^10.6', '--no-interaction'],
            ['remove', 'jeremykenedy/laravel-toast', '--no-interaction'],
            ['laravelusers:setup-package', 'toast', '--framework=bootstrap5', '--no-interaction'],
            ['laravelusers:setup-package', 'spatie', '--framework=tailwind', '--no-interaction', '--migrate'],
        ], $commands);
        $this->assertFalse($composer->setup('unapproved', 'tailwind', true, $output));
        $this->assertFalse($composer->setup('toast', 'unapproved', true, $output));
        $this->assertCount(5, file(base_path('commands.jsonl')));
    }

    public function test_missing_composer_returns_an_actionable_failure_without_starting_a_process(): void
    {
        putenv('PATH='.$this->fixturePath.'/missing');
        $output = '';
        $this->assertFalse((new ComposerPackages())->install('jeremykenedy/laravel-toast', function ($text) use (&$output) {
            $output .= $text;
        }));
        $this->assertStringContainsString('Composer was not found', $output);
        $this->assertFileDoesNotExist(base_path('commands.jsonl'));
        $this->assertFalse((new ComposerPackages())->changeFromSettings('install', 'jeremykenedy/laravel-toast'));
    }

    public function test_a_dependency_that_remains_installed_is_not_reported_as_removed(): void
    {
        File::put(base_path('package-remains'), '');
        $this->assertFalse((new ComposerPackages())->changeFromSettings('remove', 'spatie/laravel-permission'));
        $this->assertFileExists(base_path('bootstrap/cache/packages.php'));
        $this->assertStringNotContainsString('package:discover', File::get(base_path('commands.jsonl')));
    }

    public function test_failed_composer_or_discovery_does_not_report_completion(): void
    {
        $composer = new ComposerPackages();
        File::put(base_path('composer-fails'), '');
        $this->assertFalse($composer->changeFromSettings('install', 'jeremykenedy/laravel-toast'));
        $this->assertFileExists(base_path('bootstrap/cache/packages.php'));
        File::delete(base_path('composer-fails'));
        File::put(base_path('discovery-fails'), '');
        $this->assertFalse($composer->changeFromSettings('install', 'jeremykenedy/laravel-toast'));
        $commands = File::get(base_path('commands.jsonl'));
        $this->assertStringNotContainsString('queue:restart', $commands);
        $this->assertFileExists(base_path('composer.json'));
    }

    public function test_invalid_installed_metadata_cannot_report_removal_or_refresh_application_caches(): void
    {
        $composer = new ComposerPackages();
        foreach (['{', 'null', 'false', '"unexpected"', '{"packages":null}', '{"packages":false}', '{"packages":[{"version":"1.0.0"}]}'] as $payload) {
            File::put(base_path('manifest-payload'), $payload);

            $this->assertFalse($composer->changeFromSettings('remove', 'jeremykenedy/laravel-toast'));
            $this->assertFileExists(base_path('bootstrap/cache/packages.php'));
            $this->assertFileExists(base_path('bootstrap/cache/services.php'));
        }
        $this->assertStringNotContainsString('package:discover', File::get(base_path('commands.jsonl')));
        $this->assertStringNotContainsString('queue:restart', File::get(base_path('commands.jsonl')));
    }
}
