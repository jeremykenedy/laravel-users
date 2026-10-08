<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

class UpdateCommand extends InstallCommand
{
    protected $signature = 'laravelusers:update
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

    protected $description = 'Update Laravel Users views or switch frontend while preserving configuration';
}
