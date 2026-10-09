<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Composer\InstalledVersions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

class NativeRuntime
{
    public const STACKS = ['blade', 'livewire', 'vue', 'react', 'svelte'];

    public const RELEASE_STACKS = ['blade'];

    public static function name(): string
    {
        $runtime = config('laravelusers-ui.runtime', config('laravelusers.runtime', 'blade'));

        return in_array($runtime, self::STACKS, true) ? $runtime : 'blade';
    }

    public static function expectsJson(Request $request): bool
    {
        return self::name() !== 'blade' && $request->expectsJson()
            && $request->header('X-LaravelUsers-Runtime') === self::name();
    }

    public static function supportsView(View $view): bool
    {
        $name = $view->name();
        $screens = ['show-users', 'deleted-users', 'create-user', 'show-user', 'edit-user', 'settings'];
        $views = ['laravelusers::account.page', 'laravelusers::account.confirm-email', 'laravelusers::account-links.confirm'];
        foreach (['usersmanagement', 'modern'] as $directory) {
            foreach ($screens as $screen) {
                $views[] = 'laravelusers::'.$directory.'.'.$screen;
            }
        }
        if (!in_array($name, $views, true)) {
            return false;
        }
        $source = dirname(__DIR__).'/resources/views/'.str_replace('.', '/', substr($name, strlen('laravelusers::'))).'.blade.php';

        return is_file($source) && is_file($view->getPath())
            && hash_equals(hash_file('sha256', $source), hash_file('sha256', $view->getPath()));
    }

    public static function available(string $runtime): bool
    {
        if ($runtime === 'blade') {
            return true;
        }
        if (!in_array($runtime, self::STACKS, true)) {
            return false;
        }
        if ($runtime === 'livewire') {
            if (!InstalledVersions::isInstalled('livewire/livewire') || !Facade::getFacadeApplication()->bound('livewire')) {
                return false;
            }
            $version = InstalledVersions::getVersion('livewire/livewire');

            return is_string($version) && version_compare($version, '3.0.0', '>=') && version_compare($version, '5.0.0', '<');
        }

        return is_file(dirname(__DIR__).'/resources/assets/runtime-'.$runtime.'.js');
    }
}
