<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;
use jeremykenedy\laravelusers\Console\ConsolePrompts;

class InstallationChoices
{
    public function __construct(private Command $command, private bool $interactive, private array $integrations)
    {
    }

    public function choose(): array|false
    {
        if ($this->command->getLaravel()->configurationIsCached()) {
            $this->command->error('Run php artisan config:clear before changing the frontend, then rebuild your configuration cache.');

            return false;
        }
        if (!$this->frontendOptionsValid()) {
            return false;
        }
        $choices = $this->frontendChoices();

        return $this->optionsValid(...$choices) ? $choices : false;
    }

    private function frontendOptionsValid(): bool
    {
        if ($this->command->option('framework') !== null && $this->command->option('css') !== null && $this->command->option('framework') !== $this->command->option('css')) {
            $this->command->error('Use one CSS framework. --css is an alias for --framework.');

            return false;
        }

        return true;
    }

    private function frontendChoices(): array
    {
        $runtime = $this->command->option('frontend');
        $framework = $this->command->option('framework') ?? $this->command->option('css');
        $theme = $this->command->option('theme');
        $views = $this->command->option('views');

        if ($this->interactive) {
            $runtime = $runtime ?? ConsolePrompts::select($this->command, 'Frontend runtime', array_combine(NativeRuntime::RELEASE_STACKS, NativeRuntime::RELEASE_STACKS), NativeRuntime::name(), true);
            $framework = $framework ?? ConsolePrompts::search($this->command, 'CSS framework', Frontend::RELEASE_FRAMEWORKS, Frontend::framework(), true);
            $themes = ['light', 'dark', 'system'];
            $theme = $theme ?? ConsolePrompts::select($this->command, 'Color theme', array_combine($themes, $themes), Frontend::theme(), true);
            $viewChoices = ['package', 'publish'];
            $views = $views ?? ConsolePrompts::select($this->command, 'Views (existing overrides always take precedence)', array_combine($viewChoices, $viewChoices), 'package', true);
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
            || array_diff($this->command->option('with'), array_keys($this->integrations))) {
            $this->command->error('Invalid option. Use --help for supported frameworks, themes and views. Integrations: '.implode(', ', array_keys($this->integrations)).'.');

            return false;
        }
        if ($this->command->option('force') && $views !== 'publish') {
            $this->command->error('--force requires --views=publish.');

            return false;
        }

        return true;
    }

    private function runtimeValid(string $runtime): bool
    {
        if (!in_array($runtime, NativeRuntime::RELEASE_STACKS, true)) {
            $this->command->error('This release supports --frontend=blade. Other runtimes will be added in later releases.');

            return false;
        }
        if (!NativeRuntime::available($runtime)) {
            $this->command->error($runtime === 'livewire'
                ? 'Install Livewire 3 or 4 and register its service provider before selecting --frontend=livewire.'
                : 'The bundled '.$runtime.' runtime is missing. Reinstall Laravel Users before changing the frontend.');

            return false;
        }

        return true;
    }

    private function notificationsValid(): bool
    {
        $message = 'Use --toast=keep, install or remove and --notifications=alert, toast or both.';
        foreach (['toast' => ['keep', 'install', 'remove'], 'notifications' => ['alert', 'toast', 'both']] as $option => $choices) {
            $value = $this->command->option($option);
            if ($value !== null && !in_array($value, $choices, true)) {
                $this->command->error($message);

                return false;
            }
        }
        if ($this->command->option('toast') === 'remove' && in_array($this->command->option('notifications'), ['toast', 'both'], true)) {
            $this->command->error($message);

            return false;
        }
        if (in_array($this->command->option('notifications'), ['toast', 'both'], true) && $this->command->option('toast') !== 'install' && !UserNotifications::toastInstalled()) {
            $this->command->error('Install and configure Laravel Toast before selecting --notifications=toast or both.');

            return false;
        }

        return true;
    }

    private function avatarValid(): bool
    {
        $avatar = $this->command->option('avatar');
        if (($avatar !== null && !in_array($avatar, array_merge(['keep'], Avatar::SOURCES), true)) || ($this->command->option('install-avatars') && $avatar !== 'dicebear')) {
            $this->command->error('Choose a supported --avatar source. --install-avatars requires --avatar=dicebear.');

            return false;
        }

        return true;
    }

    private function rolesValid(): bool
    {
        $role = $this->command->option('roles');
        if (($role !== null && !in_array($role, RolesSetup::CHOICES, true))
            || (($this->command->option('install-roles') || $this->command->option('role-middleware') !== null) && !in_array($role, ['laravel-roles', 'spatie'], true))
            || ($this->command->option('role-middleware') !== null && trim($this->command->option('role-middleware')) === '')) {
            $this->command->error('Select --roles=laravel-roles or --roles=spatie for role installation and middleware options. Other choices: keep, none.');

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
}
