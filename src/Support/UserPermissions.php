<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Guard;

class UserPermissions
{
    public static function enabled(Model $user): bool
    {
        return config('laravelusers.rolesEnabled', false) && config('laravelusers.permissionsEnabled', false)
            && (method_exists($user, 'permissions') || method_exists($user, 'userPermissions')) && method_exists($user, 'syncPermissions');
    }

    public static function query(Model $user): Builder
    {
        $spatie = method_exists($user, 'assignRole') && class_exists(Guard::class);
        $model = config('laravelusers.permissionModel') ?: config($spatie ? 'permission.models.permission' : 'roles.models.permission');
        $query = $model::query();

        return $spatie ? $query->where('guard_name', Guard::getDefaultName($user)) : $query;
    }

    public static function rules(Model $user): array
    {
        return self::enabled($user) ? [
            'permissions_present' => ['sometimes', 'boolean'],
            'permissions'         => ['sometimes', 'array'],
            'permissions.*'       => ['required', 'distinct', function ($attribute, $value, $fail) {
                if (!is_int($value) && !is_string($value)) {
                    $fail(trans('laravelusers::ui.invalid_permission'));
                }
            }],
        ] : [];
    }

    public static function assign(Model $user, array $ids): void
    {
        $gate = config('laravelusers.permissionsGate');
        abort_if($gate && Gate::denies($gate, $user), 403);
        $ids = array_values(array_unique(array_map('strval', $ids)));
        $permissions = self::query($user)->whereKey($ids)->get();
        if ($permissions->count() !== count($ids)) {
            throw ValidationException::withMessages(['permissions' => trans('laravelusers::ui.invalid_permission')]);
        }
        $user->syncPermissions(method_exists($user, 'assignRole') ? $permissions->all() : $permissions->modelKeys());
    }

    public static function displayData(Model $user): array
    {
        if (!self::enabled($user)) {
            return [];
        }
        $relation = method_exists($user, 'assignRole') ? 'permissions' : 'userPermissions';
        $user->load($relation);

        return ['directPermissions' => $user->getRelation($relation)];
    }

    public static function formData(Model $user): array
    {
        if (!self::enabled($user)) {
            return ['permissionsEnabled' => false];
        }
        $data = self::displayData($user);

        return $data + ['permissionsEnabled' => true, 'permissions' => self::query($user)->get(), 'currentPermissions' => $data['directPermissions']->modelKeys()];
    }
}
