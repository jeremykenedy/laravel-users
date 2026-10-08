<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;

class ToastSetup
{
    public function __construct(private readonly ComposerPackages $composer)
    {
    }

    public function configure(Command $command, bool $interactive, string $framework): string|false|null
    {
        $choice = $command->option('toast');
        if ($choice === null && $interactive) {
            $choice = $command->choice('Laravel Toast integration', ['keep', 'install', 'remove'], 'keep');
        }
        $driver = $command->option('notifications');
        if ($choice === 'remove') {
            if (!$this->composer->remove('jeremykenedy/laravel-toast', fn ($text) => $command->getOutput()->write($text))) {
                $command->error('Toast removal failed. Laravel Users configuration was not changed.');

                return false;
            }
            $command->line('Composer removal completed. Published files and other application integrations were left in place.');

            return 'alert';
        }
        if ($choice === 'install' && !class_exists(ToastServiceProvider::class)) {
            if (PHP_VERSION_ID < 80200 || version_compare($command->getLaravel()->version(), '10.0.0', '<')) {
                $command->error('Laravel Toast requires PHP 8.2 or newer and Laravel 10 or newer. Existing notification settings are unchanged.');

                return false;
            }
            if (!$this->composer->install('jeremykenedy/laravel-toast', fn ($text) => $command->getOutput()->write($text))) {
                $command->error('Toast installation failed. Laravel Users configuration was not changed.');

                return false;
            }
            $command->line('Run: php artisan toast:install --css='.$framework.' --frontend=blade');
            $command->line('Then: php artisan laravelusers:update --notifications=toast');

            return null;
        }
        if ($driver === 'toast' && !UserNotifications::toastInstalled()) {
            $command->error('Install and configure Laravel Toast first: php artisan laravelusers:update --toast=install');

            return false;
        }
        if ($choice === 'install') {
            $command->line('Laravel Toast is installed. Use toast:update to change its settings; published configuration is preserved.');
        }

        return $driver;
    }
}
