<?php

namespace jeremykenedy\laravelusers\Test\Fixtures;

use jeremykenedy\LaravelRoles\Traits\HasRoleAndPermission;

class PackageRoleUser extends SoftUser
{
    use HasRoleAndPermission;

    protected $table = 'users';

    public function getForeignKey()
    {
        return 'user_id';
    }
}
