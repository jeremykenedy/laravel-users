<?php

namespace jeremykenedy\laravelusers;

use Illuminate\Support\Facades\Facade;

class LaravelUsersFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravelusers';
    }
}
