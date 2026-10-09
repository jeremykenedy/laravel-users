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
        $records = $this->model()->newQuery()->whereIn('key', ['global', 'account', 'emails', 'cleanup'])->get()->keyBy('key');
        $this->loadAccount($records->get('account')?->value ?? []);
        $emails = $records->get('emails')?->value ?? [];
        $this->loadEmailTemplates($emails['templates'] ?? []);
        $this->loadEmailOptions($emails);
        $this->loadCleanup($records->get('cleanup')?->value ?? []);
        $this->loadGlobal($records->get('global')?->value ?? []);
    }

    private function loadAccount(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, ['enabled', 'settings_enabled'], true)) {
                config(['laravelusers.account.'.$key => (bool) $value]);
            }
        }
    }

    private function loadEmailTemplates(array $templates): void
    {
        foreach ($templates as $action => $contents) {
            if (in_array($action, ['welcome', 'reset', 'restore', 'force_delete', 'goodbye'], true)) {
                foreach (['subject', 'message'] as $key) {
                    if (isset($contents[$key]) && is_string($contents[$key])) {
                        config(['laravelusers.emails.'.$action.'_'.$key => $contents[$key]]);
                    }
                }
            }
        }
    }

    private function loadEmailOptions(array $values): void
    {
        foreach (['goodbye', 'goodbye_on_delete', 'goodbye_auto_send', 'goodbye_restore', 'goodbye_force_delete', 'goodbye_retention', 'goodbye_show_expiry'] as $key) {
            if (isset($values[$key])) {
                config(['laravelusers.emails.'.$key => (bool) $values[$key]]);
            }
        }
        if (isset($values['welcome_enabled'])) {
            config(['laravelusers.welcome.enabled' => (bool) $values['welcome_enabled'] && config('laravelusers.emails.enabled', false) && config('laravelusers.emails.welcome', true)]);
        }
        foreach (['goodbye_expiry_mode', 'goodbye_duration', 'goodbye_unit'] as $key) {
            if (isset($values[$key])) {
                config(['laravelusers.emails.'.$key => $values[$key]]);
            }
        }
    }

    private function loadCleanup(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, ['enabled', 'amount', 'unit'], true)) {
                config(['laravelusers.cleanup.'.$key => $value]);
            }
        }
    }

    private function loadGlobal(array $values): void
    {
        $keys = array_merge(self::APPEARANCE, ['notifications.driver', 'notifications.dismissible', 'access', 'impersonation.enabled']);
        $booleans = ['profileCardGradient', 'editCardGradient', 'profileCardDarkGradient', 'editCardDarkGradient', 'notifications.dismissible', 'impersonation.enabled'];
        foreach ($values as $key => $value) {
            if (in_array($key, $keys, true)) {
                config(['laravelusers.'.$key => $value !== null && in_array($key, $booleans, true) ? (bool) $value : $value]);
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
