<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerPackages
{
    public function readiness(): ?string
    {
        if (!(new ExecutableFinder())->find('composer') || !function_exists('proc_open')) {
            return 'laravelusers::ui.package_composer_missing';
        }
        if (!$this->manifestReady()) {
            return 'laravelusers::ui.package_composer_manifest';
        }
        if (!$this->vendorReady()) {
            return 'laravelusers::ui.package_composer_vendor';
        }
        if (!$this->applicationReady()) {
            return 'laravelusers::ui.package_composer_application';
        }

        return null;
    }

    private function manifestReady(): bool
    {
        $manifest = base_path('composer.json');

        return is_file($manifest) && is_readable($manifest) && is_writable($manifest)
            && is_object(json_decode((string) file_get_contents($manifest)));
    }

    private function vendorReady(): bool
    {
        return is_dir(base_path('vendor')) && is_writable(base_path('vendor')) && $this->installedPackages() !== null;
    }

    private function applicationReady(): bool
    {
        $lock = base_path('composer.lock');

        return is_file(base_path('artisan')) && is_readable(base_path('artisan'))
            && is_dir(base_path('bootstrap/cache')) && is_writable(base_path('bootstrap/cache'))
            && (!file_exists($lock) || (is_file($lock) && is_writable($lock)));
    }

    /**
     * Symfony Process supplies an output type before each output chunk.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function setup(string $package, string $framework, bool $migrate, callable $output): bool
    {
        if (!isset(ManagedPackages::PACKAGES[$package]) || !in_array($framework, Frontend::FRAMEWORKS, true)) {
            return false;
        }
        $arguments = [PHP_BINARY, base_path('artisan'), 'laravelusers:setup-package', $package, '--framework='.$framework, '--no-interaction'];
        if ($migrate) {
            $arguments[] = '--migrate';
        }
        $process = new Process($arguments, base_path(), null, null, 60);

        return $process->run(fn ($type, $text) => $output($text)) === 0;
    }

    public function install(string $package, callable $output): bool
    {
        return $this->installMany([$package], $output);
    }

    public function installMany(array $packages, callable $output): bool
    {
        return $this->run('require', $packages, $output);
    }

    public function remove(string $package, callable $output): bool
    {
        return $this->run('remove', [$package], $output);
    }

    public function changeFromSettings(string $action, string $package): bool
    {
        if (!in_array($action, ['install', 'remove'], true) || !in_array($package, ManagedPackages::PACKAGES, true)) {
            return false;
        }
        $composer = (new ExecutableFinder())->find('composer');
        if (!$composer || !is_writable(base_path('composer.json')) || !is_writable(base_path('vendor'))) {
            return false;
        }
        $process = new Process([$composer, $action === 'install' ? 'require' : 'remove', $package, '--no-interaction', '--no-scripts', '--no-plugins'], base_path(), null, null, 300);
        if ($process->run() !== 0) {
            Log::error('Package change failed.', ['package' => $package, 'operation' => $action, 'exit_code' => $process->getExitCode()]);

            return false;
        }
        if (!$this->verifyChange($action, $package)) {
            return false;
        }

        return $this->refreshApplication($package);
    }

    private function verifyChange(string $action, string $package): bool
    {
        $installed = $this->installedPackages();
        if ($installed === null) {
            Log::error('Composer installed metadata could not be verified.', ['package' => $package]);

            return false;
        }
        $present = in_array($package, array_column($installed, 'name'), true);
        if ($present !== ($action === 'install')) {
            Log::error('Package remains required by another dependency.', ['package' => $package]);

            return false;
        }

        return true;
    }

    private function installedPackages(): ?array
    {
        $path = base_path('vendor/composer/installed.json');
        if (!is_file($path)) {
            return null;
        }
        $installed = json_decode((string) file_get_contents($path), true);
        if (!is_array($installed)) {
            return null;
        }
        $packages = array_key_exists('packages', $installed) ? $installed['packages'] : $installed;
        if (!is_array($packages)) {
            return null;
        }
        foreach ($packages as $package) {
            if (!is_array($package) || !is_string($package['name'] ?? null)) {
                return null;
            }
        }

        return $packages;
    }

    private function refreshApplication(string $package): bool
    {
        foreach (['packages.php', 'services.php'] as $file) {
            $path = base_path('bootstrap/cache/'.$file);
            if (is_file($path) && !unlink($path)) {
                return false;
            }
        }
        $discovery = new Process([PHP_BINARY, base_path('artisan'), 'package:discover', '--no-interaction', '--no-ansi'], base_path(), null, null, 30);
        if ($discovery->run() !== 0) {
            Log::error('Package discovery failed after changing a dependency.', ['package' => $package]);

            return false;
        }
        $restart = new Process([PHP_BINARY, base_path('artisan'), 'queue:restart', '--no-interaction', '--no-ansi'], base_path(), null, null, 10);

        return $restart->run() === 0;
    }

    /**
     * Symfony Process supplies an output type before each output chunk.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    private function run(string $action, array $packages, callable $output): bool
    {
        $composer = (new ExecutableFinder())->find('composer');
        if ($composer === null) {
            $output('Composer was not found. Install the package manually and run laravelusers:update again.');

            return false;
        }
        $process = new Process(array_merge([$composer, $action], $packages, ['--no-interaction']), base_path(), null, null, 300);

        return $process->run(fn ($type, $text) => $output($text)) === 0;
    }
}
