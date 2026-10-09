<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NativeUserData
{
    public function __construct(private Avatar $avatars, private UserActivity $activity, private NativeFormData $forms, private NativeEmailForms $emails, private NativeUserColumns $columns, private NativeUserActions $actions)
    {
    }

    public function listing(array $page, array $data, Request $request): array
    {
        $users = $data['users'];
        $deleted = $page['screen'] === 'deleted-users';
        $models = $users instanceof LengthAwarePaginator ? $users->getCollection()->all() : collect($users)->all();
        $activity = $this->activity->listing($models, (bool) config('laravelusers.showLastLoginDetailsColumn', false));
        $avatars = $this->avatars->listing($models);
        $appearance = AppearancePreferences::colors($models);
        $page['data']['users'] = array_map(fn ($user) => $this->user($user, $deleted, $activity[$user->getKey()] ?? [], $avatars[$user->getKey()] ?? null, $appearance[$user->getKey()] ?? null), $models);
        $page['data']['columns'] = $this->columns->columns($deleted);
        $page['data']['pagination'] = $this->pagination($users, $models);
        $page['data']['deleted_user'] = $deleted;
        $page['data']['search'] = (string) $request->query('user_search_box', '');
        $page = $this->bulkActions($page, $deleted, $request);

        return $page;
    }

    public function identity(Model $user): array
    {
        return ['id' => (string) $user->getKey(), 'name' => (string) $user->getAttribute('name'), 'email' => (string) $user->getAttribute('email')];
    }

    private function user(Model $user, bool $deleted, array $activity = [], ?array $avatar = null, ?array $appearance = null): array
    {
        $row = $this->identity($user) + ['roles' => config('laravelusers.rolesEnabled', false) && $user->relationLoaded('roles') ? $user->getRelation('roles')->map(fn ($role) => ['id' => (string) $role->getKey(), 'name' => (string) $role->name])->all() : [], 'avatar' => $avatar, 'activity' => $activity, 'appearance' => $appearance, 'urls' => [], 'links' => [], 'actions' => []];
        foreach (['created_at', 'updated_at', 'deleted_at'] as $key) {
            $value = $user->getAttribute($key);
            $row[$key] = $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : (is_string($value) ? $value : null);
        }
        $row = array_replace($row, $this->actions->forUser($user, $deleted));

        return $row;
    }

    public function profile(array $page, array $data): array
    {
        $user = $data['user'];
        $page['data']['user'] = $this->user($user, false, $this->activity->listing([$user], true)[$user->getKey()] ?? [], config('laravelusers.avatar.enabled', false) ? $this->avatars->forUser($user) : null, AppearancePreferences::colors([$user])[$user->getKey()] ?? null);
        $page['data']['user']['permissions'] = $this->forms->choices($data['directPermissions'] ?? []);
        $page['data']['user']['role_level'] = $data['roleLevel'] ?? null;
        $page['data']['columns'] = $this->columns->columns(false);

        return $page;
    }

    private function pagination($users, array $models): array
    {
        return $users instanceof LengthAwarePaginator ? ['enabled' => true, 'current' => $users->currentPage(), 'last' => $users->lastPage(), 'total' => $users->total(), 'from' => $users->firstItem(), 'to' => $users->lastItem(), 'previous' => $users->previousPageUrl(), 'next' => $users->nextPageUrl()] : ['enabled' => false, 'total' => count($models), 'from' => count($models) ? 1 : 0, 'to' => count($models), 'previous' => null, 'next' => null];
    }

    private function bulkActions(array $page, bool $deleted, Request $request): array
    {
        foreach ($deleted ? ['restore' => 'restore_users', 'force-delete' => 'force_delete'] : ['delete' => 'delete_users'] as $action => $ability) {
            if ($page['features']['bulk'] && UserAccess::allows($ability)) {
                $id = 'bulk-'.$action;
                $page['features']['bulk_actions'][] = ['name' => $action, 'label' => __('laravelusers::ui.'.($action === 'force-delete' ? 'permanently_delete' : $action)), 'form' => $id];
                $page['forms'][$id] = $this->forms->form($id, __('laravelusers::ui.bulk_actions'), route('users.bulk'), 'POST', [$this->forms->field('action', '', 'hidden', str_replace('-', '_', $action)), $this->forms->field('ids', '', 'hidden', [], ['multiple' => true])], $request) + ['confirm' => __('laravelusers::ui.confirm_bulk'), 'danger' => $action !== 'restore'];
            }
        }
        if ($page['features']['bulk'] && config('laravelusers.emails.bulk', true)) {
            foreach ($this->emails->actions($deleted) as $action) {
                $page['features']['bulk_actions'][] = $action;
            }
        }

        return $page;
    }
}
