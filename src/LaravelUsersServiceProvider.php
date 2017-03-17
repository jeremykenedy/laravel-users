<?php

namespace jeremykenedy\laravelusers;

use Illuminate\Support\ServiceProvider;

class LaravelUsersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views/', 'laravelusers');
		$this->loadTranslationsFrom(__DIR__.'/resources/lang/en/', 'laravelusers');

		$this->publishes([
		    __DIR__.'/resources/views/' => resource_path('views/vendor/laravelusers'),
		], 'laravelusers');

		$this->publishes([
                    __DIR__.'/resources/lang/en/' => resource_path('lang/en'),
		], 'laravelusers');

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        include __DIR__.'/routes/web.php';
        $this->app->make('jeremykenedy\laravelusers\App\Http\Controllers\UsersManagementController');
    }


}
