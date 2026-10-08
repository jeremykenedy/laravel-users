<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Guard;

class RoleAccess
{
    public static function available(?Model $user): bool
    {
        if (!$user) {
            return false;
        }
        $traits = class_uses_recursive($user);

        $spatie = in_array('Spatie\\Permission\\Traits\\HasRoles', $traits, true);
        if (!$spatie && !in_array('jeremykenedy\\LaravelRoles\\Traits\\HasRoleAndPermission', $traits, true)) {
            return false;
        }
        foreach (['role', 'permission'] as $type) {
            $model = config(($spatie ? 'permission' : 'roles').'.models.'.$type);
            if (!is_string($model) || !is_subclass_of($model, Model::class)) {
                return false;
            }
            $instance = self::query($user, $type)->getModel();
            if (!$instance->getConnection()->getSchemaBuilder()->hasTable($instance->getTable())) {
                return false;
            }
        }
        foreach (['roles', $spatie ? 'permissions' : 'userPermissions'] as $name) {
            $relation = $user->$name();
            if (!$relation->getQuery()->getConnection()->getSchemaBuilder()->hasTable($relation->getTable())) {
                return false;
            }
        }
        $permissions = self::query($user, 'role')->getModel()->permissions();
        if (!$permissions->getQuery()->getConnection()->getSchemaBuilder()->hasTable($permissions->getTable())) {
            return false;
        }

        return true;
    }

    public static function query(Model $user, string $type): Builder
    {
        $spatie = method_exists($user, 'hasPermissionTo');
        $model = config(($spatie ? 'permission' : 'roles').'.models.'.$type);
        $instance = new $model();
        if ($instance->getConnectionName() === null) {
            $instance->setConnection($user->getConnectionName());
        }
        $query = $instance->newQuery();
        if ($spatie) {
            $query->where('guard_name', Guard::getDefaultName($user));
            if ($type === 'role' && config('permission.teams', false) && function_exists('getPermissionsTeamId')) {
                $column = config('permission.column_names.team_foreign_key', 'team_id');
                $query->where(fn ($roles) => $roles->whereNull($column)->orWhere($column, \getPermissionsTeamId()));
            }
        }

        return $query;
    }

    public static function matches(Model $user, array $rule): bool
    {
        $roles = empty($rule['roles']) ? [] : self::query($user, 'role')->whereKey($rule['roles'])->get();
        foreach ($roles as $role) {
            if ($user->hasRole(method_exists($user, 'hasPermissionTo') ? $role : $role->getKey())) {
                return true;
            }
        }
        $permissions = empty($rule['permissions']) ? [] : self::query($user, 'permission')->whereKey($rule['permissions'])->get();
        foreach ($permissions as $permission) {
            if (method_exists($user, 'hasPermissionTo') ? $user->hasPermissionTo($permission) : $user->hasPermission($permission->getKey())) {
                return true;
            }
        }

        return isset($rule['level']) && method_exists($user, 'level') && $user->level() >= (int) $rule['level'];
    }
}
