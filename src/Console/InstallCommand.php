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
        {--css= : Alias for --framework}
        {--frontend= : blade}
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

        if (!$this->frontendOptionsValid()) {
            return self::FAILURE;
        }

        [$framework, $theme, $views] = $this->frontendChoices();
        if (!$this->optionsValid($framework, $theme, $views)) {
            return self::FAILURE;
        }

        if ($views === 'publish' && !$this->publishViews($files)) {
            return self::FAILURE;
        }

        $this->saveConfiguration($files, $framework, $theme);
        $this->call('view:clear');
        $this->info('Laravel Users configured: '.$framework.', '.$theme.'. Existing custom view settings are preserved.');
        $this->line('Package views use installed overrides first. Remove or rename an override yourself to return to the bundled view.');
        $this->printIntegrationInstructions();

        return self::SUCCESS;
    }

    private function frontendOptionsValid(): bool
    {
        if (($this->option('framework') && $this->option('css') && $this->option('framework') !== $this->option('css'))
            || ($this->option('frontend') && $this->option('frontend') !== 'blade')) {
            $this->error('Use one CSS framework. The supported frontend is blade.');

            return false;
        }

        return true;
    }

    private function frontendChoices(): array
    {
        $framework = $this->option('framework') ?? $this->option('css');
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

        return [(string) $framework, (string) $theme, (string) $views];
    }

    private function optionsValid(string $framework, string $theme, string $views): bool
    {
        if (!$this->frontendChoicesValid($framework, $theme, $views)
            || array_diff($this->option('with'), array_keys(self::INTEGRATIONS))) {
            $this->error('Invalid option. Use --help for supported frameworks, themes and views. Integrations: '.implode(', ', array_keys(self::INTEGRATIONS)).'.');

            return false;
        }

        if ($this->option('force') && $views !== 'publish') {
            $this->error('--force requires --views=publish.');

            return false;
        }

        return true;
    }

    private function frontendChoicesValid(string $framework, string $theme, string $views): bool
    {
        return in_array($framework, Frontend::FRAMEWORKS, true)
            && in_array($theme, ['light', 'dark', 'system'], true)
            && in_array($views, ['package', 'publish'], true);
    }

    private function publishViews(Filesystem $files): bool
    {
        $destination = resource_path('views/vendor/laravelusers');
        if (!$this->backupViews($files, $destination)) {
            return false;
        }

        foreach ($files->allFiles(dirname(__DIR__).'/resources/views') as $file) {
            $target = $destination.'/'.$file->getRelativePathname();
            if (!$files->exists($target) || $this->option('force')) {
                $files->ensureDirectoryExists(dirname($target));
                if (!$files->copy($file->getPathname(), $target)) {
                    $this->error('Unable to publish view: '.$target);

                    return false;
                }
            }
        }

        return true;
    }

    private function backupViews(Filesystem $files, string $destination): bool
    {
        if (!$this->option('force') || !$files->isDirectory($destination)) {
            return true;
        }

        $backup = storage_path('app/laravelusers/backups/'.date('Ymd-His').'-'.bin2hex(random_bytes(4)));
        $files->ensureDirectoryExists(dirname($backup));
        if (!$files->copyDirectory($destination, $backup)) {
            $this->error('Unable to back up views. No views were replaced.');

            return false;
        }
        $this->info('View backup: '.$backup);

        return true;
    }

    private function saveConfiguration(Filesystem $files, string $framework, string $theme): void
    {
        $files->ensureDirectoryExists(config_path());
        $config = config_path('laravelusers.php');
        if (!$files->exists($config)) {
            $files->copy(dirname(__DIR__).'/config/laravelusers.php', $config);
        }

        $settings = array_merge(config('laravelusers-ui', []), ['framework' => $framework, 'theme' => $theme]);
        $environment = ['framework' => 'LARAVEL_USERS_FRONTEND', 'theme' => 'LARAVEL_USERS_THEME'];
        $lines = [];
        foreach ($settings as $key => $value) {
            $default = var_export($value, true);
            $export = isset($environment[$key]) ? "env('".$environment[$key]."', ".$default.')' : $default;
            $lines[] = '    '.var_export($key, true).' => '.$export.',';
        }
        $files->replace(config_path('laravelusers-ui.php'), "<?php\n\nreturn [\n".implode("\n", $lines)."\n];\n");
    }

    private function printIntegrationInstructions(): void
    {
        foreach ($this->option('with') as $integration) {
            $this->line('Optional setup: composer require jeremykenedy/laravel-'.$integration);
            if (self::INTEGRATIONS[$integration]) {
                $this->line('Then: php artisan '.self::INTEGRATIONS[$integration]);
            }
            $this->line('Follow https://github.com/jeremykenedy/laravel-'.$integration.' for host application configuration.');
        }
    }
}
