<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Console\ConsolePrompts;
use jeremykenedy\laravelusers\Support\AvatarSetup;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\HostRouting;
use jeremykenedy\laravelusers\Support\InstallationChoices;
use jeremykenedy\laravelusers\Support\InstallationConfiguration;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\PublicAssets;
use jeremykenedy\laravelusers\Support\RolesSetup;
use jeremykenedy\laravelusers\Support\ToastSetup;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Coordinates setup services while preserving the existing command entry points.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class InstallCommand extends Command
{
    protected $signature = 'laravelusers:install
        {--framework= : bootstrap4 or bootstrap5}
        {--css= : Alias for --framework}
        {--frontend= : blade}
        {--theme= : light, dark, or system}
        {--views= : package or publish}
        {--with=* : Show setup instructions for optional integrations}
        {--roles= : keep, none, laravel-roles, or spatie}
        {--setup-integrations : Publish missing configuration for the selected optional packages}
        {--migrate-integrations : Run only the selected optional package migrations}
        {--setup-packages : Set up dedicated package queue storage and cache locks}
        {--setup-accounts : Publish optional account settings migrations}
        {--install-roles : Install the explicitly selected missing roles package with Composer}
        {--role-middleware= : Middleware for the selected roles package}
        {--avatar= : keep or a supported avatar source}
        {--install-avatars : Install the local DiceBear libraries when --avatar=dicebear}
        {--toast= : keep, install, or remove Laravel Toast}
        {--notifications= : alert, toast, or both}
        {--force : Back up and replace published package views}';

    protected $description = 'Set up Laravel Users without replacing application configuration';

    private const INTEGRATIONS = [
        'ui-kit'          => 'ui-kit:install',
        'toast'           => 'toast:install',
        'darkmode-toggle' => 'darkmode:install',
        'ip-capture'      => 'ip-capture:install',
        'seedster'        => null,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->setAliases(['laravel-users:install']);
    }

    public function handle(Filesystem $files, RolesSetup $roles, AvatarSetup $avatars, ToastSetup $toast, PackageRequirements $requirements, ComposerPackages $composer, PublicAssets $assets, HostRouting $routing): int
    {
        try {
            return $this->install($files, $roles, $avatars, $toast, $requirements, $composer, $assets, $routing);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Laravel Users could not finish setup.');

            return self::FAILURE;
        }
    }

    protected function install(Filesystem $files, RolesSetup $roles, AvatarSetup $avatars, ToastSetup $toast, PackageRequirements $requirements, ComposerPackages $composer, PublicAssets $assets, HostRouting $routing): int
    {
        $this->banner();
        $choices = (new InstallationChoices($this, $this->input->isInteractive(), self::INTEGRATIONS))->choose();
        if ($choices === false) {
            return self::FAILURE;
        }
        [$framework, $theme, $views, $runtime] = $choices;
        $routePlan = ConsolePrompts::spin($this, fn () => $routing->prepare(), 'Checking host route middleware...', $this->input->isInteractive());

        ConsolePrompts::table($this, ['Area', 'Selected behavior'], [
            ['Frontend runtime', $runtime],
            ['CSS framework', $framework],
            ['Color theme', $theme],
            ['Views', $views],
        ], $this->input->isInteractive());

        $roleSettings = $roles->configure($this, $this->input->isInteractive());
        if ($roleSettings === false) {
            return self::FAILURE;
        }
        $avatarSettings = $avatars->configure($this, $this->input->isInteractive());
        if ($avatarSettings === false) {
            return self::FAILURE;
        }

        $notificationDriver = $toast->configure($this, $this->input->isInteractive(), $framework);
        if ($notificationDriver === false) {
            return self::FAILURE;
        }

        if (!$this->configureIntegrations($composer, $framework)) {
            return self::FAILURE;
        }

        $this->configurePackageRequirements($requirements);

        if ($views === 'publish' && !ConsolePrompts::spin($this, fn () => $this->publishViews($files), 'Publishing package views...', $this->input->isInteractive())) {
            return self::FAILURE;
        }

        $this->exportFiles($files, $assets, $routing, $routePlan, $choices, $roleSettings, $avatarSettings, $notificationDriver);

        return $this->finishInstallation($framework, $theme, $runtime);
    }

    private function finishInstallation(string $framework, string $theme, string $runtime): int
    {
        if ($this->option('setup-accounts') && $this->call('laravelusers:setup-accounts') !== self::SUCCESS) {
            return self::FAILURE;
        }
        $this->call('view:clear');
        ConsolePrompts::outro($this, 'Laravel Users configured: '.$runtime.', '.$framework.', '.$theme.'. Existing custom view settings are preserved.', $this->input->isInteractive());
        ConsolePrompts::note($this, 'Package views use installed overrides first. Remove or rename an override yourself to return to the bundled view.', $this->input->isInteractive());
        $this->printIntegrationInstructions();

        return self::SUCCESS;
    }

    private function exportFiles(Filesystem $files, PublicAssets $assets, HostRouting $routing, ?array $routePlan, array $choices, array $roleSettings, array $avatarSettings, ?string $notificationDriver): void
    {
        [$framework, $theme, , $runtime] = $choices;
        $published = ConsolePrompts::spin($this, fn () => $assets->publish(), 'Publishing Laravel Users assets...', $this->input->isInteractive());
        ConsolePrompts::table($this, ['Public asset', 'Destination'], array_map(fn ($name) => [$name, 'public/vendor/laravelusers/'], $published), $this->input->isInteractive());
        ConsolePrompts::spin($this, fn () => $routing->write($routePlan), 'Configuring web route middleware...', $this->input->isInteractive());
        $configuration = new InstallationConfiguration($this, $this->input->isInteractive());
        ConsolePrompts::spin($this, function () use ($configuration, $files, $framework, $theme, $runtime, $roleSettings, $avatarSettings, $notificationDriver): void {
            $configuration->saveConfiguration($files, $framework, $theme, $runtime);
            $configuration->saveRoles($files, $roleSettings);
            $configuration->saveAvatars($files, $avatarSettings);
            $configuration->saveNotifications($files, $notificationDriver);
        }, 'Updating Laravel Users configuration...', $this->input->isInteractive());
    }

    private function configurePackageRequirements(PackageRequirements $requirements): void
    {
        if ($this->option('setup-packages')) {
            $requirements->configure();
            $this->info('Package queue storage and cache locks are ready.');
            $this->line('Start a persistent worker: php artisan queue:work laravelusers-packages --timeout=360');
        }
    }

    private function configureIntegrations(ComposerPackages $composer, string $framework): bool
    {
        if (!$this->option('setup-integrations')) {
            return true;
        }
        $selected = array_filter([$this->option('roles')], fn ($package) => isset(ManagedPackages::PACKAGES[$package ?? '']));
        foreach ($selected as $package) {
            if (!$composer->setup($package, $framework, (bool) $this->option('migrate-integrations'), fn ($text) => $this->getOutput()->write($text))) {
                $this->error('Optional package setup failed. Existing Laravel Users settings were preserved.');

                return false;
            }
        }

        return true;
    }

    protected function banner(): void
    {
        $style = new SymfonyStyle($this->input, $this->output);
        if (ConsolePrompts::usesNativePrompts($this, $this->input->isInteractive()) && function_exists('Laravel\\Prompts\\intro')) {
            ConsolePrompts::intro($this, 'LARAVEL-USERS INITIALIZER', 'Set up user management and select the integrations already supported by this package.');
        } elseif ($this->input->isInteractive()) {
            $this->line('<fg=blue;options=bold>+------------------------------------------+</>');
            $this->line('<fg=blue;options=bold>|        LARAVEL-USERS INITIALIZER         |</>');
            $this->line('<fg=blue;options=bold>+------------------------------------------+</>');
            $style->text('Set up user management and select the integrations already supported by this package.');
            $style->newLine();
        }
    }

    private function publishViews(Filesystem $files): bool
    {
        $destination = resource_path('views/vendor/laravelusers');
        if (!$this->backupViews($files, $destination)) {
            return false;
        }

        foreach ($files->allFiles(dirname(__DIR__, 3).'/resources/views') as $file) {
            $target = $destination.'/'.$file->getRelativePathname();
            if (!$files->exists($target) || $this->option('force')) {
                $files->ensureDirectoryExists(dirname($target));
                if (!$this->copyAtomically($files, $file->getPathname(), $target)) {
                    $this->error('Unable to publish view: '.$target);

                    return false;
                }
            }
        }

        return true;
    }

    private function copyAtomically(Filesystem $files, string $source, string $target): bool
    {
        $contents = $files->get($source);
        $mode = $files->exists($target) ? fileperms($target) & 0777 : null;
        $files->replace($target, $contents, $mode);

        return $files->exists($target) && $files->get($target) === $contents;
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
