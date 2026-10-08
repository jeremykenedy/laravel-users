<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\Model;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\UserSettings;

class UpdateAccountAccess
{
    public function __construct(private readonly UserSettings $settings)
    {
    }

    public function handle(Model $actor, array $data): void
    {
        abort_unless($this->settings->available() && AccountPreferences::available($actor), 409, trans('laravelusers::ui.account_migration_required'));
        $settings = $this->settings->model();
        abort_unless($settings->getConnection()->getName() === $actor->getConnection()->getName(), 409, trans('laravelusers::ui.account_shared_connection'));
        $actor->getConnection()->transaction(function () use ($actor, $data) {
            $stored = $this->settings->model()->newQuery()->whereKey('account')->lockForUpdate()->first()?->value ?? [];
            $values = ['enabled' => (bool) ($stored['enabled'] ?? config('laravelusers.account.enabled', false)), 'settings_enabled' => (bool) ($stored['settings_enabled'] ?? config('laravelusers.account.settings_enabled', false))];
            foreach (empty($data['apply_all']) ? ['enabled', 'settings_enabled'] : [$data['apply_all']] as $key) {
                $values[$key] = (bool) $data[$key];
            }
            $this->settings->save($values, 'account');
            if (!empty($data['apply_all'])) {
                AccountPreferences::resetOverrides($actor, $data['apply_all']);
            }
        });
    }
}
