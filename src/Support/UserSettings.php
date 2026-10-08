<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use jeremykenedy\laravelusers\Models\UserSetting;

class UserSettings
{
    public const APPEARANCE = ['avatar.source', 'profileCardColor', 'editCardColor', 'profileCardGradient', 'profileCardGradientStrength', 'editCardGradient', 'editCardGradientStrength', 'profileCardDarkColor', 'profileCardDarkGradient', 'profileCardDarkGradientStrength', 'editCardDarkColor', 'editCardDarkGradient', 'editCardDarkGradientStrength'];

    public function model(): UserSetting
    {
        $userModel = config('laravelusers.defaultUserModel');
        $user = new $userModel();

        return (new UserSetting())->setConnection(config('laravelusers.settings.connection') ?? $user->getConnectionName());
    }

    public function available(): bool
    {
        $model = $this->model();

        return $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable());
    }

    public function load(): void
    {
        if (config('laravelusers.settings.defaults') === null) {
            config(['laravelusers.settings.defaults' => array_combine(self::APPEARANCE, array_map(fn ($key) => config('laravelusers.'.$key), self::APPEARANCE))]);
        }
        if (!config('laravelusers.settings.enabled', false) || !$this->available()) {
            return;
        }
        $settings = $this->model()->newQuery()->find('global');
        $account = $this->model()->newQuery()->find('account');
        foreach ($account->value ?? [] as $key => $value) {
            if (in_array($key, ['enabled', 'settings_enabled'], true)) {
                config(['laravelusers.account.'.$key => (bool) $value]);
            }
        }
        $emails = $this->model()->newQuery()->find('emails');
        foreach ($emails->value['templates'] ?? [] as $action => $contents) {
            if (in_array($action, ['welcome', 'reset', 'restore', 'force_delete', 'goodbye'], true)) {
                foreach (['subject', 'message'] as $key) {
                    if (isset($contents[$key]) && is_string($contents[$key])) {
                        config(['laravelusers.emails.'.$action.'_'.$key => $contents[$key]]);
                    }
                }
            }
        }
        foreach (['goodbye', 'goodbye_on_delete', 'goodbye_auto_send', 'goodbye_restore', 'goodbye_force_delete', 'goodbye_retention', 'goodbye_show_expiry'] as $key) {
            if (isset($emails->value[$key])) {
                config(['laravelusers.emails.'.$key => (bool) $emails->value[$key]]);
            }
        }
        if (isset($emails->value['welcome_enabled'])) {
            config(['laravelusers.welcome.enabled' => (bool) $emails->value['welcome_enabled'] && config('laravelusers.emails.enabled', false) && config('laravelusers.emails.welcome', true)]);
        }
        foreach (['goodbye_expiry_mode', 'goodbye_duration', 'goodbye_unit'] as $key) {
            if (isset($emails->value[$key])) {
                config(['laravelusers.emails.'.$key => $emails->value[$key]]);
            }
        }
        $cleanup = $this->model()->newQuery()->find('cleanup');
        foreach ($cleanup->value ?? [] as $key => $value) {
            if (in_array($key, ['enabled', 'amount', 'unit'], true)) {
                config(['laravelusers.cleanup.'.$key => $value]);
            }
        }
        foreach ($settings->value ?? [] as $key => $value) {
            if (in_array($key, array_merge(self::APPEARANCE, ['notifications.driver', 'notifications.dismissible', 'access', 'impersonation.enabled']), true)) {
                config(['laravelusers.'.$key => $value !== null && in_array($key, ['profileCardGradient', 'editCardGradient', 'profileCardDarkGradient', 'editCardDarkGradient', 'notifications.dismissible', 'impersonation.enabled'], true) ? (bool) $value : $value]);
            }
        }
    }

    public function save(array $data, string $key = 'global'): void
    {
        $model = $this->model();
        $model->getConnection()->transaction(function () use ($model, $data, $key) {
            $model->newQuery()->updateOrCreate(['key' => $key], ['value' => $data]);
        });
    }
}
