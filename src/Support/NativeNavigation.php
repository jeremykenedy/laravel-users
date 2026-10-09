<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NativeNavigation
{
    public function __construct(private NativeEmailForms $emails)
    {
    }

    public function urls(bool $public, Request $request, string $screen): array
    {
        if ($public) {
            return Route::has('login') ? ['login' => route('login')] : [];
        }
        $names = $this->managementRoutes();
        $names += $this->accountRoutes($request);
        if ($this->emails->actions($screen === 'deleted-users')) {
            $names['email'] = 'users.email';
            if (config('laravelusers.emails.preview', true)) {
                $names['email_preview'] = 'users.email.preview';
            }
        }
        if (config('laravelusers.bulkActions', false) && UserAccess::selectable($screen === 'deleted-users')) {
            $names['bulk'] = 'users.bulk';
        }

        return array_map(fn ($name) => Route::has($name) ? route($name) : null, $names);
    }

    public function navigation(Request $request, bool $public): array
    {
        if ($public) {
            return [];
        }
        $links = UserAccess::allows('view_users') ? [['label' => __('laravelusers::app.nav.users'), 'url' => route('users')]] : [];
        if (UserAccess::allows('create_users')) {
            $links[] = ['label' => __('laravelusers::laravelusers.create-new-user'), 'url' => route('users.create')];
        }
        if (config('laravelusers.softDeletedEnabled', false) && UserAccess::allows('view_deleted')) {
            $links[] = ['label' => __('laravelusers::laravelusers.show-deleted-users'), 'url' => route('users.deleted')];
        }
        if (config('laravelusers.settings.enabled', false) && UserAccess::allows('edit_settings')) {
            $links[] = ['label' => __('laravelusers::ui.settings'), 'url' => route('users.settings')];
        }
        $links = array_merge($links, $this->accountLink($request));

        return $links;
    }

    public function breadcrumbs(array $page): array
    {
        $crumbs = [['label' => __('laravelusers::ui.home'), 'url' => Frontend::homeUrl(), 'native' => false]];
        if (in_array($page['screen'], ['account', 'account-link', 'confirm-email'], true)) {
            return array_merge($crumbs, [['label' => $page['title']]]);
        }
        if ($page['screen'] !== 'users' && isset($page['urls']['users'])) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_users'), 'url' => $page['urls']['users']];
        }
        $crumbs = array_merge($crumbs, $this->editBreadcrumb($page));
        $crumbs[] = ['label' => $page['screen'] === 'show-user' ? $page['data']['user']['name'] : $page['title']];

        return $crumbs;
    }

    private function managementRoutes(): array
    {
        $names = [];
        foreach (['users' => ['view_users', 'users', true], 'deleted' => ['view_deleted', 'users.deleted', config('laravelusers.softDeletedEnabled', false)], 'create' => ['create_users', 'users.create', true], 'settings' => ['edit_settings', 'users.settings', config('laravelusers.settings.enabled', false)], 'search' => ['view_users', 'search-users', config('laravelusers.enableSearchUsers', true)]] as $key => [$ability, $route, $enabled]) {
            if ($enabled && UserAccess::allows($ability)) {
                $names[$key] = $route;
            }
        }

        return $names;
    }

    private function accountRoutes(Request $request): array
    {
        $names = [];
        if ($request->user() instanceof Model) {
            if (config('laravelusers.showLogout', true)) {
                $names['logout'] = 'logout';
            }
            if (AccountPreferences::enabled($request->user())) {
                $names['account'] = 'users.account';
            }
        }

        return $names;
    }

    private function accountLink(Request $request): array
    {
        $links = [];
        if ($request->user() instanceof Model && AccountPreferences::enabled($request->user())) {
            $links[] = ['label' => __('laravelusers::ui.account_title'), 'url' => route('users.account')];
        }

        return $links;
    }

    private function editBreadcrumb(array $page): array
    {
        $crumbs = [];
        if (($page['data']['deleted_user'] ?? false) && $page['screen'] === 'edit-user' && isset($page['urls']['deleted'])) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_deleted_users'), 'url' => $page['urls']['deleted']];

            return $crumbs;
        }
        if ($page['screen'] === 'edit-user' && isset($page['data']['user'], $page['urls']['users'])) {
            $crumbs[] = ['label' => $page['data']['user']['name'], 'url' => route('users.show', $page['data']['user']['id'])];
        }

        return $crumbs;
    }
}
