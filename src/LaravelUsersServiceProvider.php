<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers;

use Illuminate\Support\ServiceProvider;
use jeremykenedy\laravelusers\App\Http\Controllers\UsersManagementController;

class LaravelUsersServiceProvider extends ServiceProvider
{
    private readonly string $_packageTag;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->_packageTag = 'laravelusers';
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/resources/lang/', $this->_packageTag);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/resources/views/', $this->_packageTag);
        $this->mergeConfigFrom(__DIR__.'/config/'.$this->_packageTag.'.php', $this->_packageTag);
        $this->publishFiles();
    }

    /**
     * Publish files for the package.
     *
     * @return void
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
