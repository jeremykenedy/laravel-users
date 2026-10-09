<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Guard;

class UserRoles
{
    public static function viewData(Model $user): array
    {
        $enabled = (bool) config('laravelusers.rolesEnabled', false);
        if ($enabled) {
            $user->loadMissing('roles');
        }

        return ['rolesEnabled' => $enabled, 'roleLevel' => $enabled ? (method_exists($user, 'level') ? $user->level() : $user->roles->max(fn ($role) => $role->getAttributes()['level'] ?? null)) : null];
    }

    public static function query(Model $user): Builder
    {
        $model = config('laravelusers.roleModel');
        $query = $model::query();
        if (method_exists($user, 'assignRole') && class_exists(Guard::class)) {
            $query->where('guard_name', Guard::getDefaultName($user));
            if (config('permission.teams', false) && function_exists('getPermissionsTeamId')) {
                $column = config('permission.column_names.team_foreign_key', 'team_id');
                $query->where(fn ($roles) => $roles->whereNull($column)->orWhere($column, \getPermissionsTeamId()));
            }
        }

        return $query;
    }

    /**
     * The existing replacement option preserves append and synchronization behavior for both role providers.
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public static function assign(Model $user, mixed $selection, bool $replace = false): void
    {
        $ids = array_values(array_unique(array_map('strval', (array) $selection)));
        $roles = self::query($user)->whereKey($ids)->get();
        if ($roles->count() !== count($ids)) {
            throw ValidationException::withMessages(['role' => trans('laravelusers::ui.invalid_role')]);
        }
        if (method_exists($user, 'assignRole') && method_exists($user, 'syncRoles')) {
            $replace ? $user->syncRoles($roles->all()) : $user->assignRole($roles->all());

            return;
        }
        if ($replace) {
            $user->detachAllRoles();
        }
        $user->attachRole($roles->modelKeys());
    }
}
