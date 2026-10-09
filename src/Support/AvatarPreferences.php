<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Models\AvatarPreference;

class AvatarPreferences
{
    public static function enabled(): bool
    {
        return (bool) config('laravelusers.avatar.per_user', false);
    }

    public static function available(Model $user): bool
    {
        return (self::enabled() || (config('laravelusers.account.avatar', true) && AccountPreferences::enabled($user)))
            && $user->getConnection()->getSchemaBuilder()->hasTable((new AvatarPreference())->getTable());
    }

    /**
     * Laravel passes the attribute, value, and failure callback to validation closures.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public static function rules(Model $user): array
    {
        return (self::enabled() || self::available($user)) ? ['avatar_source' => array_merge(UserAccess::allows('edit_user_appearance') ? ['sometimes', 'required'] : ['prohibited'], [Rule::in(array_merge(['inherit'], Avatar::SOURCES)), function ($attribute, $value, $fail) use ($user) {
            if (!self::available($user)) {
                $fail(trans('laravelusers::ui.avatar_migration_required'));
            }
        }])] : [];
    }

    public static function save(Model $user, array $data): void
    {
        if (!self::available($user) || !array_key_exists('avatar_source', $data)) {
            return;
        }
        $key = self::key($user);
        if ($data['avatar_source'] === 'inherit') {
            self::query($user)->whereKey($key)->delete();
        } else {
            self::query($user)->updateOrCreate(['user_key' => $key], ['source' => $data['avatar_source']]);
        }
    }

    public static function listing(iterable $users): array
    {
        $keys = [];
        $model = null;
        foreach ($users as $user) {
            $keys[self::key($user)] = $user->getKey();
            $model = $user;
        }
        if (!$model || !self::available($model)) {
            return [];
        }
        $sources = [];
        foreach (self::query($model)->whereKey(array_keys($keys))->get() as $preference) {
            $sources[$keys[$preference->user_key]] = $preference->source;
        }

        return $sources;
    }

    public static function formData(Model $user): array
    {
        return ['avatarSourceEnabled' => (self::enabled() || self::available($user)) && UserAccess::allows('edit_user_appearance'), 'avatarSourceAvailable' => self::available($user), 'avatarSource' => self::listing([$user])[$user->getKey()] ?? 'inherit'];
    }

    public static function forget(Model $user): void
    {
        if (self::available($user)) {
            self::query($user)->whereKey(self::key($user))->delete();
        }
    }

    private static function query(Model $user): Builder
    {
        return (new AvatarPreference())->setConnection($user->getConnectionName())->newQuery();
    }

    public static function key(Model $user): string
    {
        return hash('sha256', implode('|', [get_class($user), $user->getConnectionName() ?? config('database.default'), $user->getTable(), $user->getKey()]));
    }
}
