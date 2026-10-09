<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\UserPermissions;
use jeremykenedy\laravelusers\Support\UserRoles;

class UpdateUser
{
    public function handle(Model $user, array $data): void
    {
        $user->getConnection()->transaction(function () use ($user, $data): void {
            $user->name = strip_tags($data['name']);
            if (array_key_exists('email', $data)) {
                $user->email = $data['email'];
            }
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (config('laravelusers.rolesEnabled', false)) {
                UserRoles::assign($user, $data['role'], true);
            }
            if (UserPermissions::enabled($user) && (!empty($data['permissions_present']) || array_key_exists('permissions', $data))) {
                UserPermissions::assign($user, $data['permissions'] ?? []);
            }
            $user->save();
            AvatarPreferences::save($user, $data);
            AppearancePreferences::save($user, $data);
            AccountPreferences::save($user, $data);
        });
    }
}
