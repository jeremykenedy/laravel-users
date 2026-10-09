<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;

class NativeUserActions
{
    public function __construct(private NativeEmailForms $emails)
    {
    }

    public function forUser(Model $user, bool $deleted): array
    {
        $row = ['urls' => [], 'links' => [], 'actions' => []];
        $row = array_replace($row, $this->links($user, $deleted));
        $self = (string) auth()->id() === (string) $user->getKey();
        if (!$deleted && !$self && UserAccess::allows('delete_users')) {
            $row['actions'][] = ['name' => 'delete', 'label' => __('laravelusers::ui.delete'), 'form' => 'delete-user', 'url' => route('user.destroy', $user->getKey()), 'class' => 'lu-danger', 'values' => ['name' => $user->name]];
        }
        $row['actions'] = array_merge($row['actions'], $this->deletedActions($user, $deleted, $self));
        if (!$deleted && UserAccess::canImpersonate($user)) {
            $row['actions'][] = ['name' => 'impersonate', 'label' => __('laravelusers::ui.impersonation_target'), 'form' => 'impersonate-user', 'url' => route('users.impersonate', $user->getKey())];
        }
        foreach ($this->emails->actions($deleted) as $action) {
            $row['actions'][] = $action + ['values' => ['ids' => [(string) $user->getKey()]]];
        }
        $row['selectable'] = !$self || count($this->emails->actions($deleted)) > 0;

        return $row;
    }

    private function links(Model $user, bool $deleted): array
    {
        $row = ['urls' => [], 'links' => []];
        if (!$deleted) {
            $row['urls']['show'] = route('users.show', $user->getKey());
            $row['links'][] = ['name' => 'show', 'label' => __('laravelusers::ui.show'), 'url' => $row['urls']['show'], 'class' => 'lu-success'];
        }
        if (UserAccess::allows($deleted ? 'edit_deleted' : 'edit_users') && (!$deleted || config('laravelusers.settings.enabled', false))) {
            $row['urls']['edit'] = route($deleted ? 'users.deleted.edit' : 'users.edit', $user->getKey());
            $row['links'][] = ['name' => 'edit', 'label' => __('laravelusers::ui.edit'), 'url' => $row['urls']['edit']];
        }

        return $row;
    }

    private function deletedActions(Model $user, bool $deleted, bool $self): array
    {
        $row = ['actions' => []];
        foreach ($deleted ? ['restore' => ['restore_users', 'users.restore', 'restore-user', 'restore'], 'force-delete' => ['force_delete', 'users.force-destroy', 'force-delete-user', 'permanently_delete']] : [] as $action => [$ability, $route, $form, $label]) {
            if (UserAccess::allows($ability) && ($action !== 'force-delete' || !$self)) {
                $row['actions'][] = ['name' => $action, 'label' => __('laravelusers::ui.'.$label), 'form' => $form, 'url' => route($route, $user->getKey()), 'class' => $action === 'restore' ? 'lu-success' : 'lu-danger'];
            }
        }

        return $row['actions'];
    }
}
