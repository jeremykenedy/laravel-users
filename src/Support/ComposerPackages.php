<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ComposerPackages
{
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
        $installed = json_decode((string) file_get_contents(base_path('vendor/composer/installed.json')), true);
        $present = in_array($package, array_column($installed['packages'] ?? $installed ?? [], 'name'), true);
        if ($present !== ($action === 'install')) {
            Log::error('Package remains required by another dependency.', ['package' => $package]);

            return false;
        }
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
