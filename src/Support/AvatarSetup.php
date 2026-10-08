<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Console\Command;

class AvatarSetup
{
    public function __construct(private readonly ComposerPackages $composer)
    {
    }

    public function configure(Command $command, bool $interactive): array|false
    {
        $source = $command->option('avatar');
        if ($source === null && $interactive) {
            $source = $command->choice('Avatar source (keep preserves current settings)', array_merge(['keep'], Avatar::SOURCES), 'keep');
        }
        if ($source === null || $source === 'keep') {
            return [];
        }
        if ($source === 'dicebear' && !LocalAvatars::diceBearInstalled()) {
            $install = $command->option('install-avatars');
            if (!$install && $interactive) {
                $install = $command->confirm('Install the local DiceBear libraries with Composer now? Requires PHP 8.2 or newer.', false);
            }
            if (!$install) {
                $command->line('Local DiceBear: composer require dicebear/core:^10.7 dicebear/styles:^10.6');
                $command->warn('Install the libraries, then run php artisan laravelusers:update --avatar=dicebear. Existing avatar settings are unchanged.');

                return [];
            }
            if (PHP_VERSION_ID < 80200) {
                $command->error('Local DiceBear requires PHP 8.2 or newer. Use local UI Avatars or configure your own DiceBear server. Existing avatar settings are unchanged.');

                return false;
            }
            if (!$this->composer->installMany(['dicebear/core:^10.7', 'dicebear/styles:^10.6'], fn ($text) => $command->getOutput()->write($text))) {
                $command->error('Avatar installation failed. Laravel Users configuration was not changed. Review Composer output and the host lock file.');

                return false;
            }
            $command->line('DiceBear installed locally. Restart long-running workers after deployment.');
        }
        if (in_array($source, array_merge(['gravatar'], Avatar::GRAVATAR_STYLES), true)) {
            $command->warn('This source contacts Gravatar from the browser. Use initials, UI Avatars or local DiceBear for local generation.');
        }

        return $source === 'dicebear' ? ['source' => $source, 'dicebear' => ['driver' => 'local']] : ['source' => $source];
    }
}
