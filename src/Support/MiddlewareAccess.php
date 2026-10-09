<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Throwable;

class MiddlewareAccess
{
    private const TYPES = [
        'Illuminate\\Auth\\Middleware\\Authenticate'                          => 'auth',
        'Illuminate\\Auth\\Middleware\\Authorize'                             => 'can',
        'Illuminate\\Auth\\Middleware\\EnsureEmailIsVerified'                 => 'verified',
        'jeremykenedy\\LaravelRoles\\Middleware\\VerifyRole'                  => 'laravel_role',
        'jeremykenedy\\LaravelRoles\\Middleware\\VerifyPermission'            => 'laravel_permission',
        'jeremykenedy\\LaravelRoles\\Middleware\\VerifyLevel'                 => 'level',
        'jeremykenedy\\LaravelRoles\\App\\Http\\Middleware\\VerifyRole'       => 'laravel_role',
        'jeremykenedy\\LaravelRoles\\App\\Http\\Middleware\\VerifyPermission' => 'laravel_permission',
        'jeremykenedy\\LaravelRoles\\App\\Http\\Middleware\\VerifyLevel'      => 'level',
        'Spatie\\Permission\\Middleware\\RoleMiddleware'                      => 'spatie_role',
        'Spatie\\Permission\\Middleware\\PermissionMiddleware'                => 'spatie_permission',
        'Spatie\\Permission\\Middleware\\RoleOrPermissionMiddleware'          => 'spatie_role_or_permission',
        'Spatie\\Permission\\Middlewares\\RoleMiddleware'                     => 'spatie_role',
        'Spatie\\Permission\\Middlewares\\PermissionMiddleware'               => 'spatie_permission',
        'Spatie\\Permission\\Middlewares\\RoleOrPermissionMiddleware'         => 'spatie_role_or_permission',
    ];

    public static function allows(Model $actor, array $entries, ?Model $target = null): bool
    {
        try {
            return self::matches($actor, $entries, $target, []);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private static function matches(Model $actor, array $entries, ?Model $target, array $visited): bool
    {
        $groups = Route::getMiddlewareGroups();
        foreach ($entries as $entry) {
            if (!is_string($entry) || $entry === '') {
                return false;
            }
            if (isset($groups[$entry])) {
                if (in_array($entry, $visited, true) || count($visited) >= 64
                    || !self::matches($actor, $groups[$entry], $target, [...$visited, $entry])) {
                    return false;
                }
            } elseif (!self::entryAllows($actor, $entry, $target)) {
                return false;
            }
        }

        return true;
    }

    private static function entryAllows(Model $actor, string $entry, ?Model $target): bool
    {
        [$name, $arguments] = array_pad(explode(':', $entry, 2), 2, '');
        $class = Route::getMiddleware()[$name] ?? $name;
        if (!is_string($class)) {
            return false;
        }
        $class = ltrim($class, '\\');
        $type = self::TYPES[$class] ?? null;
        $values = $arguments === '' ? [] : explode(',', $arguments);
        if ($type === null) {
            $mapping = config('laravelusers.authorization.middleware_gates', []);
            $gate = is_array($mapping) ? ($mapping[$name] ?? $mapping[$class] ?? null) : null;

            return is_string($gate) && $gate !== '' && Gate::forUser($actor)->allows($gate, [$target, $values]);
        }

        return self::knownMiddlewareAllows($actor, $type, $arguments, $values);
    }

    private static function knownMiddlewareAllows(Model $actor, string $type, string $arguments, array $values): bool
    {
        return match ($type) {
            'auth'               => $values === [] || in_array(config('auth.defaults.guard', 'web'), $values, true),
            'verified'           => !$actor instanceof MustVerifyEmail || $actor->hasVerifiedEmail(),
            'can'                => self::gateAllows($actor, $values),
            'level'              => self::levelAllows($actor, $values),
            'laravel_role'       => $values !== [] && method_exists($actor, 'hasRole') && $actor->hasRole($arguments),
            'laravel_permission' => $values !== [] && method_exists($actor, 'hasPermission') && $actor->hasPermission($arguments),
            default              => self::spatieAllows($actor, $type, $values),
        };
    }

    private static function levelAllows(Model $actor, array $values): bool
    {
        return count($values) === 1 && filter_var($values[0], FILTER_VALIDATE_INT) !== false
            && method_exists($actor, 'level') && $actor->level() >= (int) $values[0];
    }

    private static function gateAllows(Model $actor, array $values): bool
    {
        $ability = array_shift($values);
        if (!is_string($ability) || $ability === '') {
            return false;
        }
        $arguments = [];
        foreach ($values as $value) {
            if (str_contains($value, '\\')) {
                $arguments[] = trim($value);

                continue;
            }
            if (preg_match('/^[\'\"](.*)[\'\"]$/', trim($value), $matches)) {
                $arguments[] = $matches[1];

                continue;
            }

            return false;
        }

        return Gate::forUser($actor)->allows($ability, $arguments);
    }

    private static function spatieAllows(Model $actor, string $type, array $values): bool
    {
        if (!self::spatieArgumentsValid($values)) {
            return false;
        }
        $names = explode('|', $values[0]);
        $role = in_array($type, ['spatie_role', 'spatie_role_or_permission'], true)
            && method_exists($actor, 'hasAnyRole') && $actor->hasAnyRole($names);
        $permission = in_array($type, ['spatie_permission', 'spatie_role_or_permission'], true)
            && method_exists($actor, 'hasAnyPermission') && Gate::forUser($actor)->any($names);

        return $role || $permission;
    }

    private static function spatieArgumentsValid(array $values): bool
    {
        return $values !== [] && count($values) <= 2
            && (!isset($values[1]) || $values[1] === config('auth.defaults.guard', 'web'));
    }
}
