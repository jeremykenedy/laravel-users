<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Preserves the existing authorization helper API and keeps each permission check subject to method complexity limits.
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class UserAccess
{
    public const ACTIONS = ['view_users', 'create_users', 'edit_users', 'delete_users', 'view_deleted', 'edit_deleted', 'restore_users', 'force_delete', 'impersonate_users', 'email_message', 'email_reset', 'email_welcome', 'email_goodbye', 'email_deleted', 'edit_settings', 'edit_appearance', 'edit_user_appearance', 'edit_notifications', 'edit_cleanup', 'edit_email_templates', 'edit_account_access'];

    public static function canImpersonate(?Model $target = null, ?Model $actor = null): bool
    {
        $user = $actor ?? Auth::user();

        return $user instanceof Model && self::impersonationAvailable($user)
            && self::managementMiddlewareAllows($user, $target)
            && MiddlewareAccess::allows($user, (array) config('laravelusers.impersonation.middleware', []), $target)
            && self::allows('impersonate_users', actor: $user)
            && (!$target || ((string) $target->getKey() !== (string) $user->getKey() && $target::class === config('laravelusers.defaultUserModel')));
    }

    public static function impersonationAvailable(?Model $actor = null): bool
    {
        $actor ??= Auth::user();

        return config('laravelusers.impersonation.enabled', false) && $actor instanceof Model
            && RoleAccess::available($actor) && self::allows('view_users', actor: $actor)
            && self::allows('impersonate_users', actor: $actor);
    }

    public static function allows(string $action, ?array $rules = null, ?Model $actor = null): bool
    {
        if (!in_array($action, self::ACTIONS, true)) {
            return false;
        }
        if (!config('laravelusers.settings.enabled', false)) {
            return $action !== 'edit_settings';
        }
        if ($actor === null && $rules === null && request()->attributes->has('laravelusers.access')) {
            return request()->attributes->get('laravelusers.access')[$action] ?? false;
        }
        $user = $actor ?? Auth::user();
        $rule = ($rules ?? config('laravelusers.access', []))[$action] ?? ['mode' => 'inherit'];

        return is_array($rule) && self::matchesRule($action, $user, $rule);
    }

    private static function matchesRule(string $action, mixed $user, array $rule): bool
    {
        if (($rule['mode'] ?? 'inherit') === 'inherit') {
            return $action !== 'edit_settings' || ($user && Gate::forUser($user)->allows(config('laravelusers.settings.gate', 'manage-laravelusers-settings')));
        }

        return ($rule['mode'] ?? '') === 'restricted' && $user instanceof Model && RoleAccess::available($user) && RoleAccess::matches($user, $rule);
    }

    public static function canManageUsers(?Model $user): bool
    {
        if (!$user || !self::allows('view_users', actor: $user)) {
            return false;
        }
        foreach (self::middleware() as $entry) {
            if (is_string($entry) && !self::middlewareAllows($user, $entry)) {
                return false;
            }
        }

        return true;
    }

    private static function middleware(): array
    {
        $middleware = (array) config('laravelusers.middleware', []);
        if (config('laravelusers.rolesEnabled', false) && config('laravelusers.rolesMiddlwareEnabled', true)) {
            return array_merge($middleware, (array) config('laravelusers.rolesMiddlware', 'role:admin'));
        }

        return $middleware;
    }

    public static function managementMiddlewareAllows(Model $actor, ?Model $target = null): bool
    {
        return MiddlewareAccess::allows($actor, self::middleware(), $target);
    }

    private static function middlewareAllows(Model $user, string $entry): bool
    {
        [$name, $arguments] = array_pad(explode(':', $entry, 2), 2, '');
        $values = array_filter(array_map('trim', preg_split('/[|,]/', $arguments) ?: []));
        if (!$values) {
            return true;
        }

        return match ($name) {
            'role'       => method_exists($user, 'hasRole') && self::hasOneOf($user, 'hasRole', $values),
            'permission' => self::hasPermission($user, $values),
            'can'        => isset($values[0]) && Gate::forUser($user)->allows($values[0]),
            default      => true,
        };
    }

    private static function hasOneOf(Model $user, string $method, array $values): bool
    {
        foreach ($values as $value) {
            if ($user->$method($value)) {
                return true;
            }
        }

        return false;
    }

    private static function hasPermission(Model $user, array $permissions): bool
    {
        $method = method_exists($user, 'hasPermissionTo') ? 'hasPermissionTo' : (method_exists($user, 'hasPermission') ? 'hasPermission' : null);

        return $method !== null && self::hasOneOf($user, $method, $permissions);
    }

    /**
     * The existing deleted-user option selects the separate email authorization policy.
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public static function email(string $action, bool $deleted = false): bool
    {
        return in_array($action, ['message', 'reset', 'welcome'], true) && self::allows($deleted ? 'email_deleted' : 'email_'.$action);
    }

    /**
     * The existing deleted-user option selects restore and permanent deletion policies.
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public static function selectable(bool $deleted = false): bool
    {
        $actions = $deleted ? ['restore_users', 'force_delete'] : ['delete_users'];
        if (count(array_filter($actions, fn ($action) => self::allows($action))) > 0) {
            return true;
        }
        if (!config('laravelusers.emails.enabled', false) || !config('laravelusers.emails.bulk', true)) {
            return false;
        }
        $actions = $deleted ? ['message'] : ['message', 'reset', 'welcome'];

        return count(array_filter($actions, fn ($action) => config('laravelusers.emails.'.$action, true) && self::email($action, $deleted))) > 0;
    }
}
