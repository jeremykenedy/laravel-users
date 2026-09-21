<?php

namespace jeremykenedy\laravelusers\Test;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\LaravelUsersServiceProvider;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelUsersServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('laravelusers.defaultUserModel', User::class);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('session.driver', 'array');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('view.paths', [__DIR__.'/Fixtures/views']);
        $app['config']->set('laravelusers.enableAppCss', false);
        $app['config']->set('laravelusers.enableAppJs', false);
    }

    protected function defineRoutes($router)
    {
        Route::get('login', function () {
            return 'Login';
        })->name('login');
        Route::get('register', function () {
            return 'Register';
        })->name('register');
        Route::post('logout', function () {
            return 'Logout';
        })->name('logout');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function user(array $attributes = []): User
    {
        return User::create(array_merge([
            'name'     => 'person'.User::count(),
            'email'    => 'person'.User::count().'@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
