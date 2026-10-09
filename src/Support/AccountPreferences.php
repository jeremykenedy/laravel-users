<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Models\AccountPreference;
use jeremykenedy\laravelusers\Models\EmailChange;

class AccountPreferences
{
    public static function available(Model $user): bool
    {
        return $user->getConnection()->getSchemaBuilder()->hasTable((new AccountPreference())->getTable());
    }

    public static function enabled(Model $user, string $setting = 'enabled'): bool
    {
        if (get_class($user) !== config('laravelusers.defaultUserModel') || !self::available($user)) {
            return false;
        }
        $value = self::query($user)->find(AvatarPreferences::key($user))?->$setting;

        return (bool) ($value ?? config('laravelusers.account.'.$setting, false));
    }

    public static function editable(Model $user): bool
    {
        return self::enabled($user) && self::enabled($user, 'settings_enabled');
    }

    public static function rules(Model $user): array
    {
        $rules = [];
        foreach (['enabled', 'settings_enabled'] as $field) {
            $rules['account_'.$field] = [UserAccess::allows('edit_account_access') ? 'sometimes' : 'prohibited', Rule::in(['inherit', 'on', 'off']), function ($attribute, $value, $fail) use ($user) {
                if (!self::available($user)) {
                    $fail(trans('laravelusers::ui.account_migration_required'));
                }
            }];
        }

        return $rules;
    }

    public static function save(Model $user, array $data): void
    {
        $values = [];
        foreach (['enabled', 'settings_enabled'] as $field) {
            if (array_key_exists('account_'.$field, $data)) {
                $values[$field] = ['on' => true, 'off' => false][$data['account_'.$field]] ?? null;
            }
        }
        if ($values && self::available($user)) {
            self::query($user)->updateOrCreate(['user_key' => AvatarPreferences::key($user)], ['user_type' => get_class($user)] + $values);
        }
    }

    public static function fullName(Model $user): ?string
    {
        $column = config('laravelusers.account.name_column');

        return $column ? $user->getAttribute($column) : (self::available($user) ? self::query($user)->find(AvatarPreferences::key($user))?->full_name : null);
    }

    public static function saveName(Model $user, string $name): void
    {
        self::query($user)->updateOrCreate(['user_key' => AvatarPreferences::key($user)], ['user_type' => get_class($user), 'full_name' => $name]);
    }

    public static function resetOverrides(Model $user, string $setting): void
    {
        self::query($user)->where('user_type', get_class($user))->update([$setting => null]);
    }

    public static function formData(Model $user): array
    {
        return ['accountPreferenceAvailable' => self::available($user), 'accountPreference' => self::available($user) ? self::query($user)->find(AvatarPreferences::key($user)) : null];
    }

    public static function forget(Model $user): void
    {
        if (self::available($user)) {
            self::query($user)->whereKey(AvatarPreferences::key($user))->delete();
        }
        $changes = (new EmailChange())->setConnection($user->getConnectionName());
        if ($user->getConnection()->getSchemaBuilder()->hasTable($changes->getTable())) {
            $changes->newQuery()->where('user_key', AvatarPreferences::key($user))->delete();
        }
    }

    private static function query(Model $user): Builder
    {
        return (new AccountPreference())->setConnection($user->getConnectionName())->newQuery();
    }
}
