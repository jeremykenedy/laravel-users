<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;

class SetupAccountsCommand extends Command
{
    protected $signature = 'laravelusers:setup-accounts {--migrate : Run only the optional account, avatar and appearance migrations}';

    protected $description = 'Publish optional account settings storage without enabling the feature';

    public function handle(): int
    {
        foreach (['account', 'avatar', 'appearance'] as $feature) {
            if ($this->call('vendor:publish', ['--tag' => 'laravelusers-'.$feature.'-migrations']) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        if ($this->option('migrate')) {
            $paths = array_map(fn ($feature) => dirname(__DIR__).'/database/'.$feature, ['accounts', 'avatar', 'appearance']);
            if ($this->call('migrate', ['--path' => $paths, '--realpath' => true, '--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }
        $this->info('Account migrations published. Account settings remain disabled until an administrator enables them.');
        $this->line('Run php artisan migrate if you did not use --migrate.');

        return self::SUCCESS;
    }
}
