<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;
use jeremykenedy\laravelusers\Console\ConsolePrompts;

class ToastSetup
{
    public function __construct(private readonly ComposerPackages $composer)
    {
    }

    public function configure(Command $command, bool $interactive, string $framework): string|false|null
    {
        $choice = $this->choice($command, $interactive);
        $driver = $command->option('notifications');
        if ($choice === 'remove' && in_array($driver, ['toast', 'both'], true)) {
            $command->error('Toast removal requires --notifications=alert or no notification selection.');

            return false;
        }
        if ($choice === 'remove') {
            return $this->remove($command);
        }
        if ($choice === 'install' && !class_exists(ToastServiceProvider::class)) {
            return $this->install($command, $framework, $driver);
        }

        return $this->configureInstalled($command, $choice, $framework, $driver);
    }

    private function choice(Command $command, bool $interactive): ?string
    {
        $choice = $command->option('toast');
        if ($choice === null && $interactive) {
            $choices = ['keep', 'install', 'remove'];
            $choice = ConsolePrompts::select($command, 'Laravel Toast integration', array_combine($choices, $choices), 'keep', $interactive);
        }

        return $choice;
    }

    private function configureInstalled(Command $command, ?string $choice, string $framework, ?string $driver): string|false|null
    {
        if (in_array($driver, ['toast', 'both'], true) && !UserNotifications::toastInstalled()) {
            $command->error('Install and configure Laravel Toast first: php artisan laravelusers:update --toast=install');

            return false;
        }
        if ($choice === 'install' && !$this->setup($command, $framework)) {
            return false;
        }

        return $driver;
    }

    public static function framework(string $framework): string
    {
        return in_array($framework, ['bootstrap4', 'bootstrap5', 'tailwind'], true) ? $framework : 'bootstrap5';
    }

    private function remove(Command $command): string|false
    {
        if (!$this->composer->remove('jeremykenedy/laravel-toast', fn ($text) => $command->getOutput()->write($text))) {
            $command->error('Toast removal failed. Laravel Users configuration was not changed.');

            return false;
        }
        $command->line('Composer removal completed. Published files and other application integrations were left in place.');

        return 'alert';
    }

    private function setup(Command $command, string $framework): bool
    {
        if (is_file(config_path('toast.php'))) {
            $command->line('Existing Laravel Toast configuration was preserved.');

            return true;
        }
        if (!$this->composer->setup('toast', $framework, false, fn ($text) => $command->getOutput()->write($text))) {
            $command->error('Laravel Toast was installed, but setup failed. Existing notification settings were preserved.');

            return false;
        }
        $command->info('Laravel Toast setup completed.');

        return true;
    }

    private function install(Command $command, string $framework, ?string $driver): string|false|null
    {
        if (PHP_VERSION_ID < 80200 || version_compare($command->getLaravel()->version(), '10.0.0', '<')) {
            $command->error('Laravel Toast requires PHP 8.2 or newer and Laravel 10 or newer. Existing notification settings are unchanged.');

            return false;
        }
        if (!$this->composer->install('jeremykenedy/laravel-toast', fn ($text) => $command->getOutput()->write($text))) {
            $command->error('Toast installation failed. Laravel Users configuration was not changed.');

            return false;
        }

        return $this->setup($command, $framework) ? $driver : false;
    }
}
