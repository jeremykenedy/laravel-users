<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Console\ConsolePrompts;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\AvatarSetup;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\HostRouting;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\NativeRuntime;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\PublicAssets;
use jeremykenedy\laravelusers\Support\RolesSetup;
use jeremykenedy\laravelusers\Support\ToastSetup;
use jeremykenedy\laravelusers\Support\UserNotifications;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

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
        $choices = $this->installationChoices();
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
        ConsolePrompts::spin($this, function () use ($files, $framework, $theme, $runtime, $roleSettings, $avatarSettings, $notificationDriver): void {
            $this->saveConfiguration($files, $framework, $theme, $runtime);
            $this->saveRoles($files, $roleSettings);
            $this->saveAvatars($files, $avatarSettings);
            $this->saveNotifications($files, $notificationDriver);
        }, 'Updating Laravel Users configuration...', $this->input->isInteractive());
    }

    private function installationChoices(): array|false
    {
        if ($this->laravel->configurationIsCached()) {
            $this->error('Run php artisan config:clear before changing the frontend, then rebuild your configuration cache.');

            return false;
        }
        if (!$this->frontendOptionsValid()) {
            return false;
        }
        $choices = $this->frontendChoices();

        return $this->optionsValid(...$choices) ? $choices : false;
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

    private function saveNotifications(Filesystem $files, ?string $driver): void
    {
        if ($driver === null) {
            return;
        }
        $settings = array_replace(config('laravelusers-notifications', []), ['driver' => $driver]);
        $code = $this->exportSettings($settings, ['driver' => 'LARAVEL_USERS_NOTIFICATIONS_DRIVER', 'dismissible' => 'LARAVEL_USERS_NOTIFICATIONS_DISMISSIBLE']);
        $files->replace(config_path('laravelusers-notifications.php'), "<?php\n\nreturn ".$code.";\n");
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

    private function saveRoles(Filesystem $files, array $settings): void
    {
        if ($settings === []) {
            return;
        }
        $environment = ['rolesEnabled' => 'LARAVEL_USERS_ROLES_ENABLED', 'roleModel' => 'LARAVEL_USERS_ROLE_MODEL', 'rolesMiddlwareEnabled' => 'LARAVEL_USERS_ROLES_MIDDLWARE_ENABLED', 'rolesMiddlware' => 'LARAVEL_USERS_ROLES_MIDDLWARE'];
        $this->saveRoleEnvironment($files, $settings, $environment);
        $settings = array_merge(config('laravelusers-roles', []), $settings);
        $lines = [];
        foreach ($settings as $key => $value) {
            $export = var_export($value, true);
            if (isset($environment[$key])) {
                $export = "env('".$environment[$key]."', ".$export.')';
            }
            $lines[] = '    '.var_export($key, true).' => '.$export.',';
        }
        $files->replace(config_path('laravelusers-roles.php'), "<?php\n\nreturn [\n".implode("\n", $lines)."\n];\n");
    }

    private function saveRoleEnvironment(Filesystem $files, array $settings, array $environment): void
    {
        $path = $this->laravel->environmentFilePath();
        if (!$files->isFile($path)) {
            return;
        }
        $contents = $files->get($path);
        foreach ($settings as $key => $value) {
            if (!isset($environment[$key])) {
                continue;
            }
            $name = $environment[$key];
            $pattern = '/^\h*(?:export\h+)?'.preg_quote($name, '/').'\h*=[^\r\n]*(?:\r?\n|$)/m';
            if (is_array($value)) {
                $contents = preg_replace($pattern, '', $contents);

                continue;
            }
            $literal = is_bool($value) ? ($value ? 'true' : 'false') : '"'.strtr((string) $value, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$', "\r" => '\\r', "\n" => '\\n']).'"';
            $entry = $name.'='.$literal.PHP_EOL;
            $contents = preg_match($pattern, $contents) ? preg_replace_callback($pattern, fn () => $entry, $contents) : rtrim($contents, "\r\n").PHP_EOL.$entry;
        }
        $this->replaceEnvironment($files, $path, $contents);
    }

    private function replaceEnvironment(Filesystem $files, string $path, string $contents): void
    {
        clearstatcache(true, $path);
        $path = realpath($path) ?: $path;
        $temporary = null;
        $message = 'Unable to update the environment file. Check its file and directory permissions, then retry.';

        try {
            $mode = @fileperms($path);
            $temporary = @tempnam(dirname($path), '.laravelusers-env-');
            if ($mode === false || $temporary === false || dirname($temporary) !== dirname($path)
                || !@chmod($temporary, $mode & 0777)
                || @$files->put($temporary, $contents) !== strlen($contents)
                || !@$files->move($temporary, $path)) {
                throw new RuntimeException($message);
            }
        } catch (Throwable $exception) {
            $this->error($message);

            throw new RuntimeException($message, 0, $exception);
        } finally {
            if (is_string($temporary) && is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function frontendOptionsValid(): bool
    {
        if ($this->option('framework') !== null && $this->option('css') !== null && $this->option('framework') !== $this->option('css')) {
            $this->error('Use one CSS framework. --css is an alias for --framework.');

            return false;
        }

        return true;
    }

    private function frontendChoices(): array
    {
        $runtime = $this->option('frontend');
        $framework = $this->option('framework') ?? $this->option('css');
        $theme = $this->option('theme');
        $views = $this->option('views');

        if ($this->input->isInteractive()) {
            $runtime = $runtime ?? ConsolePrompts::select($this, 'Frontend runtime', array_combine(NativeRuntime::RELEASE_STACKS, NativeRuntime::RELEASE_STACKS), NativeRuntime::name(), true);
            $framework = $framework ?? ConsolePrompts::search($this, 'CSS framework', Frontend::RELEASE_FRAMEWORKS, Frontend::framework(), true);
            $themes = ['light', 'dark', 'system'];
            $theme = $theme ?? ConsolePrompts::select($this, 'Color theme', array_combine($themes, $themes), Frontend::theme(), true);
            $viewChoices = ['package', 'publish'];
            $views = $views ?? ConsolePrompts::select($this, 'Views (existing overrides always take precedence)', array_combine($viewChoices, $viewChoices), 'package', true);
        }

        $runtime = $runtime ?? NativeRuntime::name();
        $framework = $framework ?? Frontend::framework();
        $theme = $theme ?? Frontend::theme();
        $views = $views ?? 'package';

        return [(string) $framework, (string) $theme, (string) $views, (string) $runtime];
    }

    private function optionsValid(string $framework, string $theme, string $views, string $runtime): bool
    {
        if (!$this->runtimeValid($runtime)) {
            return false;
        }
        if (!$this->notificationsValid() || !$this->avatarValid() || !$this->rolesValid()) {
            return false;
        }
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

    private function runtimeValid(string $runtime): bool
    {
        if (!in_array($runtime, NativeRuntime::RELEASE_STACKS, true)) {
            $this->error('This release supports --frontend=blade. Other runtimes will be added in later releases.');

            return false;
        }
        if (!NativeRuntime::available($runtime)) {
            $this->error($runtime === 'livewire'
                ? 'Install Livewire 3 or 4 and register its service provider before selecting --frontend=livewire.'
                : 'The bundled '.$runtime.' runtime is missing. Reinstall Laravel Users before changing the frontend.');

            return false;
        }

        return true;
    }

    private function notificationsValid(): bool
    {
        if (($this->option('toast') !== null && !in_array($this->option('toast'), ['keep', 'install', 'remove'], true))
            || ($this->option('notifications') !== null && !in_array($this->option('notifications'), ['alert', 'toast', 'both'], true))
            || ($this->option('toast') === 'remove' && in_array($this->option('notifications'), ['toast', 'both'], true))) {
            $this->error('Use --toast=keep, install or remove and --notifications=alert, toast or both.');

            return false;
        }
        if (in_array($this->option('notifications'), ['toast', 'both'], true) && $this->option('toast') !== 'install' && !UserNotifications::toastInstalled()) {
            $this->error('Install and configure Laravel Toast before selecting --notifications=toast or both.');

            return false;
        }

        return true;
    }

    private function avatarValid(): bool
    {
        $avatar = $this->option('avatar');
        if (($avatar !== null && !in_array($avatar, array_merge(['keep'], Avatar::SOURCES), true)) || ($this->option('install-avatars') && $avatar !== 'dicebear')) {
            $this->error('Choose a supported --avatar source. --install-avatars requires --avatar=dicebear.');

            return false;
        }

        return true;
    }

    private function rolesValid(): bool
    {
        $role = $this->option('roles');
        if (($role !== null && !in_array($role, RolesSetup::CHOICES, true))
            || (($this->option('install-roles') || $this->option('role-middleware') !== null) && !in_array($role, ['laravel-roles', 'spatie'], true))
            || ($this->option('role-middleware') !== null && trim($this->option('role-middleware')) === '')) {
            $this->error('Select --roles=laravel-roles or --roles=spatie for role installation and middleware options. Other choices: keep, none.');

            return false;
        }

        return true;
    }

    private function frontendChoicesValid(string $framework, string $theme, string $views): bool
    {
        return in_array($framework, Frontend::RELEASE_FRAMEWORKS, true)
            && in_array($theme, ['light', 'dark', 'system'], true)
            && in_array($views, ['package', 'publish'], true);
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

    private function saveConfiguration(Filesystem $files, string $framework, string $theme, string $runtime): void
    {
        $files->ensureDirectoryExists(config_path());
        $config = config_path('laravelusers.php');
        if (!$files->exists($config)) {
            $files->copy(dirname(__DIR__, 3).'/config/laravelusers.php', $config);
        }

        $settings = array_merge(config('laravelusers-ui', []), ['framework' => $framework, 'theme' => $theme]);
        if ($this->option('frontend') !== null || $this->input->isInteractive() || array_key_exists('runtime', $settings)) {
            $settings['runtime'] = $runtime;
        }
        $environment = ['framework' => 'LARAVEL_USERS_FRONTEND', 'theme' => 'LARAVEL_USERS_THEME', 'runtime' => 'LARAVEL_USERS_RUNTIME'];
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

    private function saveAvatars(Filesystem $files, array $settings): void
    {
        if ($settings === []) {
            return;
        }
        $settings = array_replace_recursive(config('laravelusers-avatar', []), $settings);
        $environment = ['source' => 'LARAVEL_USERS_AVATAR_SOURCE', 'enabled' => 'LARAVEL_USERS_AVATAR_ENABLED', 'per_user' => 'LARAVEL_USERS_AVATAR_PER_USER', 'attribute' => 'LARAVEL_USERS_AVATAR_ATTRIBUTE', 'fallback' => 'LARAVEL_USERS_AVATAR_FALLBACK', 'size' => 'LARAVEL_USERS_AVATAR_SIZE', 'image_size' => 'LARAVEL_USERS_AVATAR_IMAGE_SIZE', 'remote_enabled' => 'LARAVEL_USERS_AVATAR_REMOTE_ENABLED', 'dicebear.driver' => 'LARAVEL_USERS_AVATAR_DICEBEAR_DRIVER', 'dicebear.style' => 'LARAVEL_USERS_AVATAR_DICEBEAR_STYLE', 'dicebear.url' => 'LARAVEL_USERS_AVATAR_DICEBEAR_URL', 'ui_avatars.driver' => 'LARAVEL_USERS_AVATAR_UI_DRIVER', 'ui_avatars.url' => 'LARAVEL_USERS_AVATAR_UI_URL', 'ui_avatars.background' => 'LARAVEL_USERS_AVATAR_UI_BACKGROUND', 'ui_avatars.color' => 'LARAVEL_USERS_AVATAR_UI_COLOR'];
        $files->replace(config_path('laravelusers-avatar.php'), "<?php\n\nreturn ".$this->exportSettings($settings, $environment).";\n");
    }

    private function exportSettings(array $settings, array $environment, string $prefix = ''): string
    {
        $lines = [];
        foreach ($settings as $key => $value) {
            $path = $prefix.$key;
            $export = is_array($value) ? $this->exportSettings($value, $environment, $path.'.') : var_export($value, true);
            if (isset($environment[$path])) {
                $export = "env('".$environment[$path]."', ".$export.')';
            }
            $lines[] = str_repeat(' ', 4 * (substr_count($prefix, '.') + 1)).var_export($key, true).' => '.$export.',';
        }

        return "[\n".implode("\n", $lines)."\n".str_repeat(' ', 4 * substr_count($prefix, '.')).']';
    }
}
