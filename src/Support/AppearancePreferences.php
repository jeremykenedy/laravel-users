<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use jeremykenedy\laravelusers\Models\AppearancePreference;

class AppearancePreferences
{
    public static function available(Model $user): bool
    {
        return (config('laravelusers.appearance.per_user', false) || (config('laravelusers.account.appearance', true) && AccountPreferences::enabled($user)))
            && $user->getConnection()->getSchemaBuilder()->hasTable((new AppearancePreference())->getTable());
    }

    public static function rules(Model $user): array
    {
        if (!config('laravelusers.appearance.per_user', false) && !self::available($user)) {
            return [];
        }
        $allowed = UserAccess::allows('edit_user_appearance') ? 'sometimes' : 'prohibited';
        $rules = [];
        foreach (['' => self::available($user), '_dark' => self::darkAvailable($user)] as $mode => $available) {
            $readyForMode = function ($attribute, $value, $fail) use ($available) {
                if (!$available) {
                    $fail(trans('laravelusers::ui.appearance_migration_required'));
                }
            };
            $rules['user_card'.$mode.'_color'] = [$allowed, 'nullable', 'string', 'regex:/\A#[a-f0-9]{6}\z/i', $readyForMode];
            $rules['user_card'.$mode.'_gradient'] = array_merge($allowed === 'sometimes' ? ['sometimes', 'required'] : ['prohibited'], [Rule::in(['inherit', 'on', 'off']), $readyForMode]);
            $rules['user_card'.$mode.'_gradient_strength'] = [$allowed, 'nullable', 'integer', 'min:0', 'max:100', $readyForMode, function ($attribute, $value, $fail) use ($user, $mode) {
                if ($mode === '' && !self::strengthAvailable($user)) {
                    $fail(trans('laravelusers::ui.appearance_migration_required'));
                }
            }];
        }

        return $rules;
    }

    public static function save(Model $user, array $data): void
    {
        $fields = ['color' => 'user_card_color', 'gradient' => 'user_card_gradient'];
        if (self::strengthAvailable($user)) {
            $fields['gradient_strength'] = 'user_card_gradient_strength';
        }
        if (self::darkAvailable($user)) {
            $fields += ['dark_color' => 'user_card_dark_color', 'dark_gradient' => 'user_card_dark_gradient', 'dark_gradient_strength' => 'user_card_dark_gradient_strength'];
        }
        if (!self::available($user) || !array_intersect(array_keys($data), $fields)) {
            return;
        }
        $query = self::query($user);
        $key = AvatarPreferences::key($user);
        $current = $query->find($key);
        $values = [];
        foreach ($fields as $column => $input) {
            $value = array_key_exists($input, $data) ? $data[$input] : $current?->$column;
            $values[$column] = array_key_exists($input, $data) && in_array($column, ['gradient', 'dark_gradient'], true) ? (['on' => true, 'off' => false][$value] ?? null) : $value;
        }
        if (count(array_filter($values, fn ($value) => $value !== null)) === 0) {
            $query->whereKey($key)->delete();
        } else {
            $query->updateOrCreate(['user_key' => $key], $values);
        }
    }

    public static function listing(iterable $users): array
    {
        $keys = [];
        $model = null;
        foreach ($users as $user) {
            $keys[AvatarPreferences::key($user)] = $user->getKey();
            $model = $user;
        }
        if (!$model || !self::available($model)) {
            return [];
        }
        $values = [];
        foreach (self::query($model)->whereKey(array_keys($keys))->get() as $row) {
            $values[$keys[$row->user_key]] = ['color' => $row->color, 'gradient' => $row->gradient];
            foreach (['gradient_strength' => 'strength', 'dark_color' => 'dark_color', 'dark_gradient' => 'dark_gradient', 'dark_gradient_strength' => 'dark_strength'] as $column => $field) {
                if (array_key_exists($column, $row->getAttributes()) && $row->$column !== null) {
                    $values[$keys[$row->user_key]][$field] = $row->$column;
                }
            }
        }

        return $values;
    }

    public static function formData(Model $user): array
    {
        return ['appearanceEnabled' => (config('laravelusers.appearance.per_user', false) || self::available($user)) && UserAccess::allows('edit_user_appearance'), 'appearanceAvailable' => self::available($user), 'appearanceStrengthAvailable' => self::strengthAvailable($user), 'appearanceDarkAvailable' => self::darkAvailable($user), 'appearancePreference' => self::listing([$user])[$user->getKey()] ?? []];
    }

    public static function colors(iterable $users): array
    {
        $colors = [];
        foreach (self::listing($users) as $id => $values) {
            $light = Frontend::gradientColors(Frontend::colors($values['color'] ?? config('laravelusers.profileCardColor', '#2458b7')), $values['strength'] ?? config('laravelusers.profileCardGradientStrength', 50)) + ['gradient' => (bool) ($values['gradient'] ?? config('laravelusers.profileCardGradient', true))];
            $dark = Frontend::gradientColors(Frontend::colors($values['dark_color'] ?? config('laravelusers.profileCardDarkColor') ?? $light['base']), $values['dark_strength'] ?? config('laravelusers.profileCardDarkGradientStrength') ?? $light['strength']) + ['gradient' => (bool) ($values['dark_gradient'] ?? config('laravelusers.profileCardDarkGradient') ?? $light['gradient'])];
            $colors[$id] = $light + ['dark' => $dark];
        }

        return $colors;
    }

    public static function forget(Model $user): void
    {
        if (self::available($user)) {
            self::query($user)->whereKey(AvatarPreferences::key($user))->delete();
        }
    }

    private static function query(Model $user): Builder
    {
        return (new AppearancePreference())->setConnection($user->getConnectionName())->newQuery();
    }

    public static function darkAvailable(Model $user): bool
    {
        return self::available($user) && $user->getConnection()->getSchemaBuilder()->hasColumn((new AppearancePreference())->getTable(), 'dark_gradient_strength');
    }

    public static function strengthAvailable(Model $user): bool
    {
        return self::available($user) && $user->getConnection()->getSchemaBuilder()->hasColumn((new AppearancePreference())->getTable(), 'gradient_strength');
    }
}
