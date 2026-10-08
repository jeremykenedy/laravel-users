<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Filesystem\Filesystem;

class SwitchCommand extends InstallCommand
{
    protected $signature = 'laravelusers:switch
        {--framework= : bootstrap4, bootstrap5, or tailwind}
        {--css= : Alias for --framework}
        {--frontend= : blade}
        {--theme= : light, dark, or system}
        {--views= : package or publish}
        {--with=* : Show setup instructions for optional integrations}
        {--force : Back up and replace published package views}';

    protected $description = 'Switch Laravel Users frontend choices using explicit options';

    public function handle(Filesystem $files): int
    {
        if (!$this->option('framework') && !$this->option('css') && !$this->option('theme') && !$this->option('views')) {
            $this->error('Choose --framework, --css, --theme, or --views.');

            return self::FAILURE;
        }
        $this->input->setInteractive(false);

        return parent::handle($files);
    }
}
