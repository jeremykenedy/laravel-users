<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\App\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use jeremykenedy\laravelusers\Console\InstallCommand;
use jeremykenedy\laravelusers\Support\AvatarSetup;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\HostRouting;
use jeremykenedy\laravelusers\Support\PackageRequirements;
use jeremykenedy\laravelusers\Support\PublicAssets;
use jeremykenedy\laravelusers\Support\RolesSetup;
use jeremykenedy\laravelusers\Support\ToastSetup;

class SwitchCommand extends InstallCommand
{
    protected $signature = 'laravelusers:switch
        {--framework= : bootstrap4, bootstrap5, or tailwind}
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
        {--notifications= : alert or toast}
        {--force : Back up and replace published package views}';

    protected $description = 'Switch Laravel Users frontend choices using explicit options';

    public function __construct()
    {
        parent::__construct();
        $this->setAliases(['laravel-users:switch']);
    }

    public function handle(Filesystem $files, RolesSetup $roles, AvatarSetup $avatars, ToastSetup $toast, PackageRequirements $requirements, ComposerPackages $composer, PublicAssets $assets, HostRouting $routing): int
    {
        $options = ['framework', 'css', 'theme', 'views', 'roles', 'avatar', 'toast', 'notifications', 'setup-packages'];
        if (!array_filter($options, fn ($option) => $this->option($option))) {
            $this->error('Choose --framework, --css, --theme, --views, --roles, --avatar, --toast or --notifications.');

            return self::FAILURE;
        }
        $this->input->setInteractive(false);

        return parent::handle($files, $roles, $avatars, $toast, $requirements, $composer, $assets, $routing);
    }
}
