<?php

namespace jeremykenedy\laravelusers\Test\Fixtures;

use Illuminate\Database\Eloquent\SoftDeletes;

class SoftUser extends User
{
    use SoftDeletes;

    protected $table = 'users';
}
