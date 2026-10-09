<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Console\Commands;

use jeremykenedy\laravelusers\Console\ConsolePrompts;
use jeremykenedy\laravelusers\Console\InstallCommand;

class UpdateCommand extends InstallCommand
{
    protected $signature = 'laravelusers:update
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

    protected $description = 'Update Laravel Users views or switch frontend while preserving configuration';

    protected function banner(): void
    {
        if (ConsolePrompts::usesNativePrompts($this, $this->input->isInteractive()) && function_exists('Laravel\\Prompts\\intro')) {
            ConsolePrompts::intro($this, 'LARAVEL-USERS UPDATE', 'Reviewing the current package configuration and applying selected updates.');

            return;
        }

        if ($this->input->isInteractive()) {
            $this->line('<fg=blue;options=bold>+------------------------------------------+</>');
            $this->line('<fg=blue;options=bold>|            LARAVEL-USERS UPDATE          |</>');
            $this->line('<fg=blue;options=bold>+------------------------------------------+</>');
            $this->line('Reviewing the current package configuration and applying selected updates.');
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->setAliases(['laravel-users:update']);
    }
}
