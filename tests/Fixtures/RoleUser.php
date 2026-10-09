<?php

namespace jeremykenedy\laravelusers\Test\Fixtures;

class RoleUser extends User
{
    protected $table = 'users';

    public static bool $failAssignment = false;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function attachRole($role): void
    {
        if (self::$failAssignment) {
            throw new \RuntimeException('Role assignment failed');
        }
        $this->roles()->attach($role);
    }

    public function detachAllRoles(): void
    {
        $this->roles()->detach();
    }

    public function level(): int
    {
        return 1;
    }
}
