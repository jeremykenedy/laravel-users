<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Support\Frontend;

class InstallCommand extends Command
{
    protected $signature = 'laravelusers:install
        {--framework= : bootstrap4, bootstrap5, or tailwind}
        {--theme= : light, dark, or system}
        {--views= : package or publish}
        {--with=* : Show setup instructions for optional integrations}
        {--force : Back up and replace published package views}';

    protected $description = 'Set up Laravel Users without replacing application configuration';

    private const INTEGRATIONS = [
        'ui-kit'          => 'ui-kit:install',
        'toast'           => 'toast:install',
        'darkmode-toggle' => 'darkmode:install',
        'ip-capture'      => 'ip-capture:install',
        'seedster'        => null,
    ];

    public function handle(Filesystem $files): int
    {
        if ($this->laravel->configurationIsCached()) {
            $this->error('Run php artisan config:clear before changing the frontend, then rebuild your configuration cache.');

            return self::FAILURE;
        }

        $framework = $this->option('framework');
        $theme = $this->option('theme');
        $views = $this->option('views');

        if ($this->input->isInteractive()) {
            $framework = $framework ?? $this->choice('Frontend framework', Frontend::FRAMEWORKS, Frontend::framework());
            $theme = $theme ?? $this->choice('Color theme', ['light', 'dark', 'system'], Frontend::theme());
            $views = $views ?? $this->choice('Views (existing overrides always take precedence)', ['package', 'publish'], 'package');
        }

        $framework = $framework ?? Frontend::framework();
        $theme = $theme ?? Frontend::theme();
        $views = $views ?? 'package';

        if (!in_array($framework, Frontend::FRAMEWORKS, true)
            || !in_array($theme, ['light', 'dark', 'system'], true)
            || !in_array($views, ['package', 'publish'], true)
            || array_diff($this->option('with'), array_keys(self::INTEGRATIONS))) {
            $this->error('Invalid option. Use --help for supported frameworks, themes and views. Integrations: '.implode(', ', array_keys(self::INTEGRATIONS)).'.');

            return self::FAILURE;
        }

        if ($this->option('force') && $views !== 'publish') {
            $this->error('--force requires --views=publish.');

            return self::FAILURE;
        }

        $source = dirname(__DIR__);
        if ($views === 'publish') {
            $destination = resource_path('views/vendor/laravelusers');
            if ($this->option('force') && $files->isDirectory($destination)) {
                $backup = storage_path('app/laravelusers/backups/'.date('Ymd-His').'-'.bin2hex(random_bytes(4)));
                $files->ensureDirectoryExists(dirname($backup));
                if (!$files->copyDirectory($destination, $backup)) {
                    $this->error('Unable to back up views. No views were replaced.');

                    return self::FAILURE;
                }
                $this->info('View backup: '.$backup);
            }
            foreach ($files->allFiles($source.'/resources/views') as $file) {
                $target = $destination.'/'.$file->getRelativePathname();
                if (!$files->exists($target) || $this->option('force')) {
                    $files->ensureDirectoryExists(dirname($target));
                    if (!$files->copy($file->getPathname(), $target)) {
                        $this->error('Unable to publish view: '.$target);

                        return self::FAILURE;
                    }
                }
            }
        }

        $files->ensureDirectoryExists(config_path());
        $config = config_path('laravelusers.php');
        if (!$files->exists($config)) {
            $files->copy($source.'/config/laravelusers.php', $config);
        }

        $settings = array_merge(config('laravelusers-ui', []), ['framework' => $framework, 'theme' => $theme]);
        $files->replace(config_path('laravelusers-ui.php'), "<?php\n\nreturn ".var_export($settings, true).";\n");

        $this->call('view:clear');
        $this->info('Laravel Users configured: '.$framework.', '.$theme.'. Existing custom view settings are preserved.');
        $this->line('Package views use installed overrides first. Remove or rename an override yourself to return to the bundled view.');
        foreach ($this->option('with') as $integration) {
            $this->line('Optional setup: composer require jeremykenedy/laravel-'.$integration);
            if (self::INTEGRATIONS[$integration]) {
                $this->line('Then: php artisan '.self::INTEGRATIONS[$integration]);
            }
            $this->line('Follow https://github.com/jeremykenedy/laravel-'.$integration.' for host application configuration.');
        }

        return self::SUCCESS;
    }
}
