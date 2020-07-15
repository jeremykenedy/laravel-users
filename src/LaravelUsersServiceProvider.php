<?php

namespace fhddev\laravelusers;

use Illuminate\Support\ServiceProvider;

class LaravelUsersServiceProvider extends ServiceProvider
{
    protected $_packageTag = 'laravelusers';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang/', $this->_packageTag);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        if( ! config('laravelusers.useCustomRoutes'))
            $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        
        $this->loadViewsFrom(__DIR__.'/resources/views/', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__.'/config/'.$this->_packageTag.'.php', $this->_packageTag);
        $this->publishFiles();
        $this->app->make('fhddev\laravelusers\App\Http\Controllers\UsersManagementController');
        $this->app->singleton(fhddev\laravelusers\App\Http\Controllers\UsersManagementController\UsersManagementController::class, function () {
            return new App\Http\Controllers\UsersManagementController();
        });
        $this->app->alias(UsersManagementController::class, 'laravelusers');
    }

    /**
     * Publish files for the package.
     *
     * @return void
     */
    protected function publishFiles()
    {
        $publishTag = $this->_packageTag;

        $this->publishes([
            __DIR__.'/config/'.$this->_packageTag.'.php' => base_path('config/'.$this->_packageTag.'.php'),
        ], $publishTag.'-config');

        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/'.$this->_packageTag),
        ], $publishTag.'-views');

        $this->publishes([
            __DIR__.'/resources/lang' => resource_path('lang/vendor/'.$this->_packageTag),
        ], $publishTag.'-lang');
    }
}
