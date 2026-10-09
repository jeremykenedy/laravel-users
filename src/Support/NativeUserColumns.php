<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

class NativeUserColumns
{
    public function columns(bool $deleted): array
    {
        $columns = config('laravelusers.avatar.enabled', false) ? [['key' => 'avatar', 'label' => __('laravelusers::ui.avatar'), 'type' => 'avatar', 'sortable' => false]] : [];
        foreach (['id', 'name', 'email'] as $key) {
            $columns[] = ['key' => $key, 'label' => __('laravelusers::laravelusers.users-table.'.$key), 'linked' => $key === 'email' && config('laravelusers.emailLinks', false)];
        }
        if (config('laravelusers.rolesEnabled', false)) {
            $columns[] = ['key' => 'roles', 'label' => __('laravelusers::laravelusers.users-table.role')];
        }
        $columns = array_merge($columns, $this->presence($deleted), $this->dates($deleted), $this->activity());

        return $columns;
    }

    private function presence(bool $deleted): array
    {
        $columns = [];
        if (!$deleted && config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', false)) {
            $columns[] = ['key' => 'activity.online', 'label' => __('laravelusers::ui.presence'), 'type' => 'presence'];
        }

        return $columns;
    }

    private function dates(bool $deleted): array
    {
        $columns = [];
        foreach ($deleted ? ['deleted_at' => true] : ['created_at' => config('laravelusers.showCreatedColumn', true), 'updated_at' => config('laravelusers.showUpdatedColumn', true)] as $key => $visible) {
            if ($visible) {
                $columns[] = ['key' => $key, 'label' => $key === 'deleted_at' ? __('laravelusers::ui.deleted_at') : __('laravelusers::laravelusers.users-table.'.str_replace('_at', '', $key)), 'type' => 'date'];
            }
        }

        return $columns;
    }

    private function activity(): array
    {
        $columns = [];
        if (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false)) {
            $columns[] = ['key' => 'activity.last_login_at', 'label' => __('laravelusers::ui.last_login_at'), 'type' => 'date'];
        }
        if (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false)) {
            $columns[] = ['key' => 'activity', 'label' => __('laravelusers::ui.login_details'), 'type' => 'activity', 'sortable' => false];
        }

        return $columns;
    }
}
