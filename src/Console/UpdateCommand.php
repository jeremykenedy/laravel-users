<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

class UpdateCommand extends InstallCommand
{
    protected $signature = 'laravelusers:update
        {--framework= : bootstrap4, bootstrap5, or tailwind}
        {--theme= : light, dark, or system}
        {--views= : package or publish}
        {--with=* : Show setup instructions for optional integrations}
        {--force : Back up and replace published package views}';

    protected $description = 'Update Laravel Users views or switch frontend while preserving configuration';
}
