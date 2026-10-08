<?php

namespace jeremykenedy\laravelusers\Test\Fixtures;

use Spatie\Permission\Traits\HasRoles;

class SpatieRoleUser extends SoftUser
{
    use HasRoles;

    protected $table = 'users';

    protected $guard_name = 'web';
}
