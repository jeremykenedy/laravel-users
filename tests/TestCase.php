<?php

namespace jeremykenedy\laravelusers\Test;

use jeremykenedy\laravelusers\LaravelUsersFacade;
use jeremykenedy\laravelusers\LaravelUsersServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * Load package service provider.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return jeremykenedy\laravelusers\LaravelUsersServiceProvider
     */
    protected function getPackageProviders($app): void
    {
        return [LaravelUsersServiceProvider::class];
    }

    /**
     * Load package alias.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageAliases($app): void
    {
        return [
            'UsersManagementController' => LaravelUsersFacade::class,
        ];
    }
}
