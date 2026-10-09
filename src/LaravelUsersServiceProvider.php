<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;

class LaravelUsersServiceProvider extends ServiceProvider
{
    private string $_packageTag;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->_packageTag = 'laravelusers';
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(Kernel $kernel): void
    {
        $kernel->appendMiddlewareToGroup('web', App\Http\Middleware\VerifyImpersonationState::class);
        $this->configurePasswordExpiry();
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $settings = $this->app->make(Support\UserSettings::class);
            $schedule->command('laravelusers:prune-deleted')->everyMinute()->withoutOverlapping()->when(function () use ($settings) {
                $settings->load();

                return (bool) config('laravelusers.cleanup.enabled', false);
            });
        });

        if ($this->app->runningInConsole()) {
            $this->commands([App\Console\Commands\InstallCommand::class, App\Console\Commands\UpdateCommand::class, App\Console\Commands\SwitchCommand::class, App\Console\Commands\PublishCommand::class, Console\PruneAccountLinksCommand::class, Console\SetupPackageCommand::class, Console\CleanupDeletedUsersCommand::class, Console\SetupAccountsCommand::class]);
        }

        foreach ([Login::class, Authenticated::class, Logout::class] as $event) {
            $this->app['events']->listen($event, Listeners\TrackUserActivity::class);
        }
        foreach (['eloquent.restoring: *', 'eloquent.deleted: *'] as $event) {
            $this->app['events']->listen($event, [Listeners\InvalidateAccountLinks::class, 'handle']);
        }
        $this->app['view']->composer('laravelusers::partials.user-menu', View\UserMenuComposer::class);
        $this->app['view']->composer('laravelusers::partials.notifications', View\NotificationsComposer::class);
        $this->app['events']->listen('eloquent.deleted: *', [Listeners\TrackUserActivity::class, 'deleted']);
        $this->app['view']->composer([
            'laravelusers::usersmanagement.deleted-users', 'laravelusers::modern.deleted-users',
            'laravelusers::usersmanagement.show-user', 'laravelusers::modern.show-user',
            'laravelusers::usersmanagement.show-users', 'laravelusers::modern.show-users',
        ], View\ActivityComposer::class);

        $this->app['view']->composer([
            'laravelusers::usersmanagement.show-users', 'laravelusers::modern.show-users',
            'laravelusers::usersmanagement.deleted-users', 'laravelusers::modern.deleted-users',
            'laravelusers::usersmanagement.show-user', 'laravelusers::modern.show-user',
            'laravelusers::usersmanagement.edit-user', 'laravelusers::modern.edit-user',
            'laravelusers::account.page',
        ], View\AvatarComposer::class);

        $this->publishes([
            __DIR__.'/database/migrations' => database_path('migrations'),
        ], 'laravelusers-activity-migrations');

        $this->app['events']->listen('eloquent.deleted: *', [Listeners\ForgetAvatarPreference::class, 'handle']);
        $this->publishes([
            __DIR__.'/database/avatar' => database_path('migrations'),
        ], 'laravelusers-avatar-migrations');
        $this->publishes([
            __DIR__.'/database/settings' => database_path('migrations'),
        ], 'laravelusers-settings-migrations');
        $this->publishes([
            __DIR__.'/database/appearance' => database_path('migrations'),
        ], 'laravelusers-appearance-migrations');

        $this->publishes([
            __DIR__.'/database/account-links' => database_path('migrations'),
        ], 'laravelusers-account-links-migrations');
        $this->publishes([
            __DIR__.'/database/accounts' => database_path('migrations'),
        ], 'laravelusers-account-migrations');

        $this->publishes([
            __DIR__.'/resources/views/emails' => resource_path('views/vendor/laravelusers/emails'),
        ], 'laravelusers-email-views');

        $this->loadTranslationsFrom(__DIR__.'/resources/lang/', $this->_packageTag);
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/'.$this->_packageTag.'.php', $this->_packageTag);
        Support\PackageRequirements::load();
        if ($this->app['config']->has('laravelusers-avatar')) {
            $this->app['config']->set('laravelusers.avatar', array_replace_recursive($this->app['config']->get('laravelusers.avatar', []), $this->app['config']->get('laravelusers-avatar')));
        }
        foreach (['rolesEnabled', 'roleModel', 'rolesMiddlwareEnabled', 'rolesMiddlware'] as $key) {
            if ($this->app['config']->has('laravelusers-roles.'.$key)) {
                $this->app['config']->set('laravelusers.'.$key, $this->app['config']->get('laravelusers-roles.'.$key));
            }
        }
        if ($this->app['config']->has('laravelusers-notifications')) {
            $this->app['config']->set('laravelusers.notifications', array_replace($this->app['config']->get('laravelusers.notifications', []), $this->app['config']->get('laravelusers-notifications')));
        }
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views/', $this->_packageTag);
        $this->app->beforeResolving('auth.password', function () {
            $this->configurePasswordExpiry();
        });
        $this->app->extend('auth.password', function ($manager) {
            return get_class($manager) === PasswordBrokerManager::class ? new Support\ExpiringPasswordBrokerManager($this->app) : $manager;
        });
        $this->publishFiles();
    }

    private function configurePasswordExpiry(): void
    {
        $minutes = filter_var($this->app['config']->get('laravelusers.emails.reset_expire'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $broker = $this->app['config']->get('laravelusers.emails.password_broker') ?: $this->app['config']->get('auth.defaults.passwords');
        if ($minutes !== false && $this->app['config']->has('auth.passwords.'.$broker)) {
            $this->app['config']->set('auth.passwords.'.$broker.'.expire', $minutes);
        }
    }

    /**
     * Publish files for the package.
     */
    private function publishFiles(): void
    {
        $publishTag = $this->_packageTag;

        $this->publishes([
            __DIR__.'/config/'.$this->_packageTag.'.php' => function_exists('config_path')
                ? config_path($this->_packageTag.'.php')
                : base_path('config/'.$this->_packageTag.'.php'),
        ], $publishTag);

        $this->publishes([
            __DIR__.'/resources/views' => function_exists('resource_path')
                ? resource_path('views/vendor/'.$this->_packageTag)
                : base_path('resources/views/vendor/'.$this->_packageTag),
        ], $publishTag);

        $this->publishes([
            __DIR__.'/resources/lang' => function_exists('lang_path')
                ? lang_path('vendor/'.$this->_packageTag)
                : (function_exists('resource_path')
                    ? resource_path('lang/vendor/'.$this->_packageTag)
                    : base_path('lang/vendor/'.$this->_packageTag)),
        ], $publishTag);
    }
}
