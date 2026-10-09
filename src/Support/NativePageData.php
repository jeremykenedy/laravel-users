<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;

class NativePageData
{
    public const SCREENS = [
        'show-users'    => 'users',
        'deleted-users' => 'deleted-users',
        'create-user'   => 'create-user',
        'show-user'     => 'show-user',
        'edit-user'     => 'edit-user',
        'settings'      => 'settings',
    ];

    public function __construct(private Avatar $avatars, private UserActivity $activity, private ImpersonationSession $impersonation)
    {
    }

    public function forView(string $view, array $data, Request $request): array
    {
        $screen = $this->screen($view);
        $public = in_array($screen, ['account-link', 'confirm-email'], true);
        $capabilities = $public ? [] : array_combine(UserAccess::ACTIONS, array_map(fn ($action) => UserAccess::allows($action), UserAccess::ACTIONS));
        if (!UserAccess::canImpersonate()) {
            unset($capabilities['impersonate_users']);
        }
        $classKeys = ['shell', 'panel', 'scroll', 'table', 'field', 'field-label', 'field-control', 'control', 'input', 'select'];
        $page = [
            'screen'       => $screen,
            'title'        => $this->title($screen, $data),
            'framework'    => Frontend::framework(),
            'classes'      => array_combine($classKeys, array_map(fn ($key) => Frontend::classes($key), $classKeys)),
            'theme'        => Frontend::theme(),
            'csrf'         => $request->hasSession() ? $request->session()->token() : null,
            'urls'         => $this->urls($public, $request, $screen),
            'features'     => $this->features($screen),
            'capabilities' => $capabilities,
            'labels'       => $this->labels(),
            'data'         => ['navigation' => $this->navigation($request, $public), 'form_ids' => [], 'home' => url('/')],
            'forms'        => [],
            'flash'        => UserNotifications::useAlerts() ? $this->flash($request) : [],
        ];
        $page['data']['toasts'] = $request->hasSession() ? UserNotifications::toasts($request->session()) : [];
        $page = match ($screen) {
            'users', 'deleted-users'   => $this->listing($page, $data, $request),
            'create-user', 'edit-user' => $this->userForm($page, $data, $request),
            'show-user'                => $this->profile($page, $data),
            'settings'                 => $this->settings($page, $data, $request),
            'account'                  => $this->account($page, $data, $request),
            default                    => $this->confirmation($page, $data, $request),
        };
        if (!$public) {
            $page['data']['appearance_defaults'] = $this->appearanceDefaults();
        }
        if ($page['features']['breadcrumbs']) {
            $page['data']['breadcrumbs'] = $this->breadcrumbs($page);
        }
        if (collect($page['forms'])->contains(fn ($form) => in_array('password', array_column($form['fields'], 'key'), true))) {
            $page['data']['password'] = ['settings' => PasswordRules::settings($screen === 'create-user'), 'strength_labels' => array_map(fn ($strength) => __('laravelusers::ui.password_'.$strength), ['weak', 'fair', 'good', 'strong']), 'feedback_delay' => max(0, (int) config('laravelusers.password.confirmation_debounce', 2000))];
        }
        if (!$public && $request->user() instanceof Model) {
            $avatar = $this->avatars->forUser($request->user());
            if ($avatar) {
                $avatar['size'] = 28;
            }
            $page['data']['current_user'] = $this->identity($request->user()) + ['avatar' => $avatar, 'activity' => config('laravelusers.activity.login', false) ? ($this->activity->listing([$request->user()], true)[$request->user()->getKey()] ?? []) : null];
            $state = $request->hasSession() ? $this->impersonation->read($request) : null;
            if ($state) {
                $page['data']['banner'] = ['message' => $state['actor_name'].': '.$request->user()->name, 'action' => route('users.impersonation.stop'), 'label' => __('laravelusers::ui.impersonation_stop')];
            }
        }
        if (in_array($screen, ['users', 'deleted-users', 'show-user'], true)) {
            $page['forms'] += $this->emailForms($screen === 'deleted-users', $request);
            $users = $page['data']['users'] ?? [$page['data']['user'] ?? []];
            $actions = collect($users)->flatMap(fn ($user) => array_column($user['actions'] ?? [], 'name'))->all();
            if (in_array('delete', $actions, true)) {
                $page['forms']['delete-user'] = $this->deleteForm($request);
            }
            if (in_array('restore', $actions, true)) {
                $page['forms']['restore-user'] = $this->form('restore-user', __('laravelusers::ui.restore'), null, 'POST', [], $request);
                $page['forms']['restore-user']['submit'] = __('laravelusers::ui.restore');
            }
            if (in_array('force-delete', $actions, true)) {
                $page['forms']['force-delete-user'] = $this->form('force-delete-user', __('laravelusers::ui.permanently_delete'), null, 'DELETE', [], $request) + ['danger' => true, 'confirm' => __('laravelusers::ui.confirm_bulk')];
                $page['forms']['force-delete-user']['submit'] = __('laravelusers::ui.permanently_delete');
            }
            if (in_array('impersonate', $actions, true)) {
                $page['forms']['impersonate-user'] = $this->form('impersonate-user', __('laravelusers::ui.impersonation_target'), null, 'POST', [], $request);
                $page['forms']['impersonate-user']['submit'] = __('laravelusers::ui.confirm');
            }
        }

        return $page;
    }

    private function screen(string $view): string
    {
        if (in_array($view, ['laravelusers::account.page', 'laravelusers::account.confirm-email', 'laravelusers::account-links.confirm'], true)) {
            return ['laravelusers::account.page' => 'account', 'laravelusers::account.confirm-email' => 'confirm-email', 'laravelusers::account-links.confirm' => 'account-link'][$view];
        }
        $name = substr($view, strrpos($view, '.') + 1);
        if (!isset(self::SCREENS[$name]) || !preg_match('/\Alaravelusers::(?:usersmanagement|modern)\./', $view)) {
            throw new InvalidArgumentException('The selected view does not have a native screen.');
        }

        return self::SCREENS[$name];
    }

    private function title(string $screen, array $data): string
    {
        return match ($screen) {
            'users'         => __('laravelusers::laravelusers.showing-all-users'),
            'deleted-users' => __('laravelusers::laravelusers.show-deleted-users'),
            'create-user'   => __('laravelusers::laravelusers.create-new-user'),
            'show-user'     => __('laravelusers::laravelusers.showing-user-title', ['name' => $data['user']->name]),
            'edit-user'     => __('laravelusers::ui.edit').' '.($data['user']->name ?? ''),
            'account'       => __('laravelusers::ui.account_title'),
            'account-link'  => __('laravelusers::ui.account_link_title'),
            'confirm-email' => __('laravelusers::ui.account_email_confirm'),
            default         => __('laravelusers::ui.settings'),
        };
    }

    private function urls(bool $public, Request $request, string $screen): array
    {
        if ($public) {
            return Route::has('login') ? ['login' => route('login')] : [];
        }
        $names = [];
        foreach (['users' => ['view_users', 'users', true], 'deleted' => ['view_deleted', 'users.deleted', config('laravelusers.softDeletedEnabled', false)], 'create' => ['create_users', 'users.create', true], 'settings' => ['edit_settings', 'users.settings', config('laravelusers.settings.enabled', false)], 'search' => ['view_users', 'search-users', config('laravelusers.enableSearchUsers', true)]] as $key => [$ability, $route, $enabled]) {
            if ($enabled && UserAccess::allows($ability)) {
                $names[$key] = $route;
            }
        }
        if ($request->user() instanceof Model) {
            if (config('laravelusers.showLogout', true)) {
                $names['logout'] = 'logout';
            }
            if (AccountPreferences::enabled($request->user())) {
                $names['account'] = 'users.account';
            }
        }
        if ($this->emailActions($screen === 'deleted-users')) {
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

    private function features(string $screen): array
    {
        return [
            'search'                   => $screen === 'users' && (bool) config('laravelusers.enableSearchUsers', true),
            'search_debounce'          => config('laravelusers.searchDebounceEnabled', false) ? max(0, (int) config('laravelusers.searchDebounce', 500)) : null,
            'sorting'                  => (bool) config('laravelusers.tableSorting', false),
            'filtering'                => (bool) config('laravelusers.tableFiltering', false),
            'columns'                  => (bool) config('laravelusers.columnVisibility', false),
            'view_toggle'              => (bool) config('laravelusers.tableViewToggle', false),
            'bulk'                     => (bool) config('laravelusers.bulkActions', false) && UserAccess::selectable($screen === 'deleted-users'),
            'show_count'               => (bool) config('laravelusers.showUserCount', false),
            'avatar'                   => (bool) config('laravelusers.avatar.enabled', false),
            'icons'                    => (bool) config('laravelusers.iconsEnabled', true),
            'theme_toggle'             => (bool) config('laravelusers.themeToggle', true),
            'notifications'            => (bool) config('laravelusers.enablePackageBootstapAlerts', true),
            'notification_driver'      => UserNotifications::useToast() ? config('laravelusers.notifications.driver', 'toast') : 'alert',
            'notification_dismissible' => (bool) config('laravelusers.notifications.dismissible', true),
            'show_header'              => (bool) config('laravelusers.showHeader', true),
            'custom_header'            => (bool) config('laravelusers.headerView'),
            'breadcrumbs'              => (bool) config('laravelusers.showBreadcrumbs', false),
            'full_width'               => (bool) config('laravelusers.fullWidth', false),
            'responsive_table'         => (bool) config('laravelusers.responsiveTable', false),
            'password_meter'           => (bool) config('laravelusers.password.meter', false),
            'password_feedback'        => (bool) config('laravelusers.password.confirmation_feedback', false),
            'email_links'              => (bool) config('laravelusers.emailLinks', false),
            'locale'                   => config('app.locale', 'en'),
            'localize_dates'           => (bool) config('laravelusers.localizeDates', false),
            'date_style'               => config('laravelusers.dateStyle', 'short'),
            'time_style'               => config('laravelusers.timeStyle', 'short'),
            'bulk_actions'             => [],
        ];
    }

    private function labels(): array
    {
        $labels = [];
        foreach (['search', 'clear', 'back', 'edit', 'save', 'show', 'delete', 'confirm', 'close', 'previous', 'next', 'directory', 'columns', 'presence', 'online', 'offline', 'last_login_at', 'no_logins', 'device', 'os', 'browser', 'ip_address', 'email_preview', 'email_send', 'email_back_editing', 'email_recipients', 'email_preview_frame', 'password_hint', 'settings', 'account_title', 'navigation', 'package_name', 'direct_permissions', 'select_user', 'total_users', 'pagination', 'page', 'account_email_pending_to', 'role_level', 'theme_light', 'theme_dark', 'appearance_inherit', 'appearance_inherit_highlight_color', 'appearance_avatar_preview', 'settings_profile_color', 'settings_edit_color', 'settings_profile_dark_color', 'settings_edit_dark_color', 'email_template_welcome', 'email_template_reset', 'email_template_restore', 'email_template_force_delete', 'email_template_goodbye', 'password_strength', 'password_mismatch', 'package_refresh', 'package_queued', 'settings_packages', 'packages_hint', 'packages_queue_required', 'package_requirements_setup', 'package_requirements_completed', 'package_requirements_verify', 'package_install', 'package_remove', 'package_installed', 'package_not_installed', 'package_configure', 'package_requirements', 'package_requirements_hint', 'breadcrumbs', 'home'] as $key) {
            $labels[$key] = __('laravelusers::ui.'.$key);
        }

        $labels['package_requirements_reverify'] = __('laravelusers::ui.package_requirements_reverify');
        $labels['package_setup_completed'] = __('laravelusers::ui.package_setup_completed');
        $labels['theme_light'] = __('laravelusers::ui.themes.light');
        $labels['theme_dark'] = __('laravelusers::ui.themes.dark');
        foreach (['logout', 'manage_users', 'account_menu_label', 'login_details'] as $key) {
            $labels[$key] = __('laravelusers::ui.'.$key);
        }

        return $labels + ['filter' => __('laravelusers::ui.filters'), 'view' => __('laravelusers::ui.list_view'), 'table' => __('laravelusers::ui.table_view'), 'cards' => __('laravelusers::ui.card_view'), 'select_all' => __('laravelusers::ui.select_all'), 'selected' => __('laravelusers::ui.selected', ['count' => ':count']), 'empty' => __('laravelusers::laravelusers.search.no-results'), 'actions' => __('laravelusers::laravelusers.users-table.actions'), 'cancel' => __('laravelusers::forms.cancel')];
    }

    private function breadcrumbs(array $page): array
    {
        $crumbs = [['label' => __('laravelusers::ui.home'), 'url' => url('/'), 'native' => false]];
        if (in_array($page['screen'], ['account', 'account-link', 'confirm-email'], true)) {
            return array_merge($crumbs, [['label' => $page['title']]]);
        }
        if ($page['screen'] !== 'users' && isset($page['urls']['users'])) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_users'), 'url' => $page['urls']['users']];
        }
        if (($page['data']['deleted_user'] ?? false) && $page['screen'] === 'edit-user' && isset($page['urls']['deleted'])) {
            $crumbs[] = ['label' => __('laravelusers::ui.breadcrumb_deleted_users'), 'url' => $page['urls']['deleted']];
        } elseif ($page['screen'] === 'edit-user' && isset($page['data']['user'], $page['urls']['users'])) {
            $crumbs[] = ['label' => $page['data']['user']['name'], 'url' => route('users.show', $page['data']['user']['id'])];
        }
        $crumbs[] = ['label' => $page['screen'] === 'show-user' ? $page['data']['user']['name'] : $page['title']];

        return $crumbs;
    }

    private function navigation(Request $request, bool $public): array
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
        if ($request->user() instanceof Model && AccountPreferences::enabled($request->user())) {
            $links[] = ['label' => __('laravelusers::ui.account_title'), 'url' => route('users.account')];
        }

        return $links;
    }

    private function flash(Request $request): array
    {
        if (!$request->hasSession()) {
            return [];
        }
        $messages = [];
        foreach (['message', 'success', 'error', 'warning', 'status'] as $key) {
            if (is_string($request->session()->get($key))) {
                $messages[] = ['type' => $key, 'message' => $request->session()->get($key)];
            }
        }
        $errors = $request->session()->get('errors');
        if ($errors instanceof ViewErrorBag && $errors->any()) {
            $messages[] = ['type' => 'error', 'message' => implode(' ', $errors->all())];
        }

        return $messages;
    }

    private function listing(array $page, array $data, Request $request): array
    {
        $users = $data['users'];
        $deleted = $page['screen'] === 'deleted-users';
        $models = $users instanceof LengthAwarePaginator ? $users->getCollection()->all() : collect($users)->all();
        $activity = $this->activity->listing($models, (bool) config('laravelusers.showLastLoginDetailsColumn', false));
        $avatars = $this->avatars->listing($models);
        $appearance = AppearancePreferences::colors($models);
        $page['data']['users'] = array_map(fn ($user) => $this->user($user, $deleted, $activity[$user->getKey()] ?? [], $avatars[$user->getKey()] ?? null, $appearance[$user->getKey()] ?? null), $models);
        $page['data']['columns'] = $this->columns($deleted);
        $page['data']['pagination'] = $users instanceof LengthAwarePaginator ? ['enabled' => true, 'current' => $users->currentPage(), 'last' => $users->lastPage(), 'total' => $users->total(), 'from' => $users->firstItem(), 'to' => $users->lastItem(), 'previous' => $users->previousPageUrl(), 'next' => $users->nextPageUrl()] : ['enabled' => false, 'total' => count($models), 'from' => count($models) ? 1 : 0, 'to' => count($models), 'previous' => null, 'next' => null];
        $page['data']['deleted_user'] = $deleted;
        $page['data']['search'] = (string) $request->query('user_search_box', '');
        foreach ($deleted ? ['restore' => 'restore_users', 'force-delete' => 'force_delete'] : ['delete' => 'delete_users'] as $action => $ability) {
            if ($page['features']['bulk'] && UserAccess::allows($ability)) {
                $id = 'bulk-'.$action;
                $page['features']['bulk_actions'][] = ['name' => $action, 'label' => __('laravelusers::ui.'.($action === 'force-delete' ? 'permanently_delete' : $action)), 'form' => $id];
                $page['forms'][$id] = $this->form($id, __('laravelusers::ui.bulk_actions'), route('users.bulk'), 'POST', [$this->field('action', '', 'hidden', str_replace('-', '_', $action)), $this->field('ids', '', 'hidden', [], ['multiple' => true])], $request) + ['confirm' => __('laravelusers::ui.confirm_bulk'), 'danger' => $action !== 'restore'];
            }
        }
        if ($page['features']['bulk'] && config('laravelusers.emails.bulk', true)) {
            foreach ($this->emailActions($deleted) as $action) {
                $page['features']['bulk_actions'][] = $action;
            }
        }

        return $page;
    }

    private function columns(bool $deleted): array
    {
        $columns = config('laravelusers.avatar.enabled', false) ? [['key' => 'avatar', 'label' => __('laravelusers::ui.avatar'), 'type' => 'avatar', 'sortable' => false]] : [];
        foreach (['id', 'name', 'email'] as $key) {
            $columns[] = ['key' => $key, 'label' => __('laravelusers::laravelusers.users-table.'.$key), 'linked' => $key === 'email' && config('laravelusers.emailLinks', false)];
        }
        if (config('laravelusers.rolesEnabled', false)) {
            $columns[] = ['key' => 'roles', 'label' => __('laravelusers::laravelusers.users-table.role')];
        }
        if (!$deleted && config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', false)) {
            $columns[] = ['key' => 'activity.online', 'label' => __('laravelusers::ui.presence'), 'type' => 'presence'];
        }
        foreach ($deleted ? ['deleted_at' => true] : ['created_at' => config('laravelusers.showCreatedColumn', true), 'updated_at' => config('laravelusers.showUpdatedColumn', true)] as $key => $visible) {
            if ($visible) {
                $columns[] = ['key' => $key, 'label' => $key === 'deleted_at' ? __('laravelusers::ui.deleted_at') : __('laravelusers::laravelusers.users-table.'.str_replace('_at', '', $key)), 'type' => 'date'];
            }
        }
        if (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false)) {
            $columns[] = ['key' => 'activity.last_login_at', 'label' => __('laravelusers::ui.last_login_at'), 'type' => 'date'];
        }
        if (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false)) {
            $columns[] = ['key' => 'activity', 'label' => __('laravelusers::ui.login_details'), 'type' => 'activity', 'sortable' => false];
        }

        return $columns;
    }

    private function identity(Model $user): array
    {
        return ['id' => (string) $user->getKey(), 'name' => (string) $user->getAttribute('name'), 'email' => (string) $user->getAttribute('email')];
    }

    private function user(Model $user, bool $deleted = false, array $activity = [], ?array $avatar = null, ?array $appearance = null): array
    {
        $row = $this->identity($user) + ['roles' => config('laravelusers.rolesEnabled', false) && $user->relationLoaded('roles') ? $user->getRelation('roles')->map(fn ($role) => ['id' => (string) $role->getKey(), 'name' => (string) $role->name])->all() : [], 'avatar' => $avatar, 'activity' => $activity, 'appearance' => $appearance, 'urls' => [], 'links' => [], 'actions' => []];
        foreach (['created_at', 'updated_at', 'deleted_at'] as $key) {
            $value = $user->getAttribute($key);
            $row[$key] = $value instanceof DateTimeInterface ? $value->format(DATE_ATOM) : (is_string($value) ? $value : null);
        }
        if (!$deleted) {
            $row['urls']['show'] = route('users.show', $user->getKey());
            $row['links'][] = ['name' => 'show', 'label' => __('laravelusers::ui.show'), 'url' => $row['urls']['show'], 'class' => 'lu-success'];
        }
        if (UserAccess::allows($deleted ? 'edit_deleted' : 'edit_users') && (!$deleted || config('laravelusers.settings.enabled', false))) {
            $row['urls']['edit'] = route($deleted ? 'users.deleted.edit' : 'users.edit', $user->getKey());
            $row['links'][] = ['name' => 'edit', 'label' => __('laravelusers::ui.edit'), 'url' => $row['urls']['edit']];
        }
        $self = (string) auth()->id() === (string) $user->getKey();
        if (!$deleted && !$self && UserAccess::allows('delete_users')) {
            $row['actions'][] = ['name' => 'delete', 'label' => __('laravelusers::ui.delete'), 'form' => 'delete-user', 'url' => route('user.destroy', $user->getKey()), 'class' => 'lu-danger', 'values' => ['name' => $user->name]];
        }
        foreach ($deleted ? ['restore' => ['restore_users', 'users.restore', 'restore-user', 'restore'], 'force-delete' => ['force_delete', 'users.force-destroy', 'force-delete-user', 'permanently_delete']] : [] as $action => [$ability, $route, $form, $label]) {
            if (UserAccess::allows($ability) && ($action !== 'force-delete' || !$self)) {
                $row['actions'][] = ['name' => $action, 'label' => __('laravelusers::ui.'.$label), 'form' => $form, 'url' => route($route, $user->getKey()), 'class' => $action === 'restore' ? 'lu-success' : 'lu-danger'];
            }
        }
        if (!$deleted && UserAccess::canImpersonate($user)) {
            $row['actions'][] = ['name' => 'impersonate', 'label' => __('laravelusers::ui.impersonation_target'), 'form' => 'impersonate-user', 'url' => route('users.impersonate', $user->getKey())];
        }
        foreach ($this->emailActions($deleted) as $action) {
            $row['actions'][] = $action + ['values' => ['ids' => [(string) $user->getKey()]]];
        }
        $row['selectable'] = !$self || count($this->emailActions($deleted)) > 0;

        return $row;
    }

    private function profile(array $page, array $data): array
    {
        $user = $data['user'];
        $page['data']['user'] = $this->user($user, false, $this->activity->listing([$user], true)[$user->getKey()] ?? [], config('laravelusers.avatar.enabled', false) ? $this->avatars->forUser($user) : null, AppearancePreferences::colors([$user])[$user->getKey()] ?? null);
        $page['data']['user']['permissions'] = $this->choices($data['directPermissions'] ?? []);
        $page['data']['user']['role_level'] = $data['roleLevel'] ?? null;
        $page['data']['columns'] = $this->columns(false);

        return $page;
    }

    private function userForm(array $page, array $data, Request $request): array
    {
        $user = $data['user'] ?? null;
        $editing = $user instanceof Model;
        $deleted = (bool) ($data['deletedUser'] ?? false);
        $fields = [$this->field('name', __('laravelusers::forms.create_user_label_username'), 'text', $editing ? $user->name : '', ['required' => true, 'section' => 'profile', 'maxlength' => 255]), $this->field('email', __('laravelusers::forms.create_user_label_email'), 'email', $editing ? $user->email : '', ['required' => true, 'section' => 'profile', 'maxlength' => 255]), $this->field('password', __('laravelusers::forms.create_user_label_password'), 'password', '', ['required' => !$editing, 'section' => 'password', 'help' => $editing ? __('laravelusers::ui.password_hint') : null]), $this->field('password_confirmation', __('laravelusers::forms.create_user_label_pw_confirmation'), 'password', '', ['required' => !$editing, 'section' => 'password'])];
        if ($data['rolesEnabled'] ?? false) {
            $fields[] = $this->field('role', __('laravelusers::forms.create_user_label_role'), 'select', $editing ? array_map('strval', $data['currentRole'] ?? []) : '', ['options' => $this->choices($data['roles'] ?? [], true), 'multiple' => $editing, 'required' => true, 'section' => 'roles']);
        }
        if ($data['permissionsEnabled'] ?? false) {
            $fields[] = $this->field('permissions_present', '', 'hidden', 1);
            $fields[] = $this->field('permissions', __('laravelusers::ui.direct_permissions'), 'select', array_map('strval', $data['currentPermissions'] ?? []), ['options' => $this->choices($data['permissions'] ?? []), 'multiple' => true, 'section' => 'roles']);
        }
        $fields = array_merge($fields, $this->appearanceFields($data));
        if (($data['accountPreferenceAvailable'] ?? false) && UserAccess::allows('edit_account_access')) {
            foreach (['enabled', 'settings_enabled'] as $key) {
                $value = ($data['accountPreference'] ?? null)?->$key;
                $fields[] = $this->field('account_'.$key, __('laravelusers::ui.account_'.$key), 'select', $value === null ? 'inherit' : ($value ? 'on' : 'off'), ['options' => $this->options(['inherit', 'on', 'off'], 'appearance_'), 'section' => 'account']);
            }
        }
        if (!$editing && config('laravelusers.emails.enabled', false) && config('laravelusers.welcome.enabled', false) && UserAccess::email('welcome')) {
            $fields[] = $this->field('send_welcome_email', __('laravelusers::ui.send_welcome'), 'checkbox', false, ['section' => 'welcome']);
            if (config('laravelusers.welcome.force_password_reset', true) && UserAccess::email('reset')) {
                $fields[] = $this->field('force_password_reset', __('laravelusers::ui.force_reset'), 'checkbox', false, ['section' => 'welcome', 'help' => __('laravelusers::ui.reset_hint')]);
            }
        }
        $form = $this->form('user', $page['title'], $editing ? route($deleted ? 'users.deleted.update' : 'users.update', $user->getKey()) : route('users.store'), $editing ? 'PUT' : 'POST', $fields, $request);
        $form['tabs'] = $editing;
        $form['confirm'] = $editing && config('laravelusers.confirmSave', true) ? __('laravelusers::modals.edit_user__modal_text_confirm_message') : null;
        $page['forms']['user'] = $form;
        $page['data']['form_ids'] = ['user'];
        $page['data']['deleted_user'] = $deleted;
        if ($editing) {
            $page['data']['user'] = $this->identity($user) + ['avatar' => $this->avatars->forUser($user), 'appearance' => AppearancePreferences::colors([$user])[$user->getKey()] ?? null];
        }

        return $page;
    }

    private function appearanceFields(array $data, bool $avatar = true, bool $appearance = true): array
    {
        $fields = [];
        if ($avatar && ($data['avatarSourceEnabled'] ?? false)) {
            $fields[] = $this->field('avatar_source', __('laravelusers::ui.avatar_source'), 'select', $data['avatarSource'] ?? 'inherit', ['options' => $this->options(array_merge(['inherit'], Avatar::SOURCES), 'avatar_source_'), 'disabled' => !($data['avatarSourceAvailable'] ?? false), 'section' => 'appearance']);
        }
        if (!$appearance || !($data['appearanceEnabled'] ?? false)) {
            return $fields;
        }
        foreach (['' => $data['appearanceAvailable'] ?? false, '_dark' => $data['appearanceDarkAvailable'] ?? false] as $mode => $available) {
            if ($mode !== '' && !$available) {
                continue;
            }
            $preference = $data['appearancePreference'] ?? [];
            $key = $mode === '' ? '' : 'dark_';
            $fields[] = $this->field('user_card'.$mode.'_color', __('laravelusers::ui.'.($mode === '' ? 'settings_profile_color' : 'settings_profile_dark_color')), 'color', $preference[$key.'color'] ?? null, ['nullable' => true, 'fallback' => Frontend::profileColors(dark: $mode !== '')['base'], 'disabled' => !$available, 'section' => 'appearance']);
            $gradient = $preference[$key.'gradient'] ?? null;
            $fields[] = $this->field('user_card'.$mode.'_gradient', __('laravelusers::ui.appearance_gradient'), 'select', $gradient === null ? 'inherit' : ($gradient ? 'on' : 'off'), ['options' => $this->options(['inherit', 'on', 'off'], 'appearance_'), 'disabled' => !$available, 'section' => 'appearance']);
            if ($mode !== '' || ($data['appearanceStrengthAvailable'] ?? false)) {
                $fields[] = $this->field('user_card'.$mode.'_gradient_strength', __('laravelusers::ui.gradient_strength'), 'range', $preference[$key.'strength'] ?? null, ['nullable' => true, 'fallback' => Frontend::profileColors(dark: $mode !== '')['strength'], 'min' => 0, 'max' => 100, 'disabled' => !$available, 'section' => 'appearance']);
            }
            if ($data['appearanceHighlightAvailable'] ?? false) {
                $prefix = $mode === '' ? 'profileCard' : 'profileCardDark';
                $fields[] = $this->field('user_card'.$mode.'_gradient_highlight_color', __('laravelusers::ui.gradient_highlight_color'), 'color', $preference[$key.'highlight_color'] ?? null, ['nullable' => true, 'fallback' => config('laravelusers.'.$prefix.'GradientHighlightColor') ?? config('laravelusers.profileCardGradientHighlightColor', '#ffffff'), 'inherit_from' => $mode !== '' && config('laravelusers.profileCardDarkGradientHighlightColor') === null ? 'user_card_gradient_highlight_color' : null, 'inherit_label' => __('laravelusers::ui.appearance_inherit_highlight_color'), 'disabled' => !$available, 'section' => 'appearance']);
            }
        }

        return $fields;
    }

    private function field(string $key, string $label, string $type = 'text', mixed $value = '', array $attributes = []): array
    {
        $parts = explode('.', $key);
        $name = array_shift($parts).implode('', array_map(fn ($part) => '['.$part.']', $parts));

        return $attributes + ['key' => $key, 'name' => $name.(!empty($attributes['multiple']) ? '[]' : ''), 'label' => $label, 'type' => $type, 'value' => $value, 'section' => 'profile', 'required' => false, 'disabled' => false];
    }

    private function appearanceDefaults(): array
    {
        $colors = [];
        foreach (['profile', 'edit', 'profile_dark', 'edit_dark'] as $kind) {
            $editing = str_starts_with($kind, 'edit');
            $dark = str_ends_with($kind, '_dark');
            $prefix = $editing ? 'editCard' : 'profileCard';
            $colors[$kind] = Frontend::profileColors($prefix.'Color', $editing ? '#705000' : '#2458b7', $dark) + ['gradient' => (bool) (($dark ? config('laravelusers.'.$prefix.'DarkGradient') : null) ?? config('laravelusers.'.$prefix.'Gradient', true)), 'highlight_color' => ($dark ? config('laravelusers.'.$prefix.'DarkGradientHighlightColor') : null) ?? config('laravelusers.'.$prefix.'GradientHighlightColor', '#ffffff'), 'highlight_inherits_light' => $dark && config('laravelusers.'.$prefix.'DarkGradientHighlightColor') === null];
        }

        return $colors;
    }

    private function form(string $id, string $title, ?string $action, string $method, array $fields, Request $request): array
    {
        $values = [];
        foreach ($fields as $field) {
            $value = $field['type'] === 'password' ? '' : ($request->hasSession() ? $request->session()->getOldInput($field['key'], $field['value']) : $field['value']);
            if ($field['type'] === 'checkbox') {
                $value = in_array($value, [true, 1, '1', 'true', 'on'], true);
            }
            Arr::set($values, $field['key'], $value);
        }
        $errors = $request->hasSession() ? $request->session()->get('errors') : null;

        return ['id' => $id, 'title' => $title, 'action' => $action, 'method' => $method, 'fields' => $fields, 'values' => $values, 'errors' => $errors instanceof ViewErrorBag ? $errors->getBag('default')->messages() : [], 'submit' => __('laravelusers::ui.save')];
    }

    private function choices(iterable $models, bool $levels = false): array
    {
        $choices = [];
        foreach ($models as $model) {
            $label = (string) $model->name;
            if ($levels && config('laravelusers.showRoleLevels', true) && isset($model->getAttributes()['level'])) {
                $label .= ' ('.__('laravelusers::ui.role_level', ['level' => $model->getAttribute('level')]).')';
            }
            $choices[] = ['value' => (string) $model->getKey(), 'label' => $label];
        }

        return $choices;
    }

    private function options(array $values, string $prefix): array
    {
        return array_map(fn ($value) => ['value' => $value, 'label' => __('laravelusers::ui.'.$prefix.$value)], $values);
    }

    private function emailActions(bool $deleted): array
    {
        $gate = config('laravelusers.emails.gate');
        if (!auth()->check() || !config('laravelusers.emails.enabled', false) || ($gate && Gate::denies($gate)) || ($deleted && !config('laravelusers.emails.deleted', true))) {
            return [];
        }
        $actions = [];
        foreach ($deleted ? ['message'] : ['message', 'reset', 'welcome'] as $action) {
            if (config('laravelusers.emails.'.$action, true) && UserAccess::email($action, $deleted) && ($action !== 'welcome' || config('laravelusers.welcome.enabled', false))) {
                $actions[] = ['name' => 'email-'.$action, 'label' => __('laravelusers::ui.email_'.$action), 'form' => 'email-'.$action, 'disabled' => $action === 'reset' && !Route::has('password.reset')];
            }
        }

        return $actions;
    }

    private function emailForms(bool $deleted, Request $request): array
    {
        $forms = [];
        foreach ($this->emailActions($deleted) as $action) {
            $kind = substr($action['name'], 6);
            $fields = [$this->field('action', '', 'hidden', $kind), $this->field('deleted', '', 'hidden', (int) $deleted), $this->field('email_form', '', 'hidden', 1), $this->field('ids', '', 'hidden', [], ['multiple' => true])];
            if ($kind === 'message' || config('laravelusers.emails.edit_'.$kind, true)) {
                $fields = array_merge($fields, $this->emailFields($kind));
            }
            if ($kind === 'reset' && config('laravelusers.emails.reset_duration', true)) {
                $broker = config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords');
                $fields = array_merge($fields, $this->expiryFields('reset', (int) config('auth.passwords.'.$broker.'.expire', 60), (bool) config('laravelusers.emails.reset_allow_never_expire', true)));
            }
            if ($deleted && config('laravelusers.account_links.enabled', false)) {
                foreach (['restore' => 'restore_users', 'force_delete' => 'force_delete'] as $link => $ability) {
                    if (config('laravelusers.account_links.'.$link, true) && UserAccess::allows($ability)) {
                        $fields[] = $this->field('include_'.$link, __('laravelusers::ui.account_include_'.$link), 'checkbox', false, ['section' => 'account-links']);
                    }
                }
                $fields = array_merge($fields, $this->expiryFields('account', (int) config('laravelusers.account_links.expire', 60), (bool) config('laravelusers.account_links.allow_never_expire', true)));
            }
            $forms[$action['form']] = $this->form($action['form'], $action['label'], route('users.email'), 'POST', $fields, $request) + ['dialog' => true, 'preview' => config('laravelusers.emails.preview', true) ? route('users.email.preview') : null];
            $forms[$action['form']]['submit'] = __('laravelusers::ui.email_send');
        }

        return $forms;
    }

    private function emailFields(string $kind, string $prefix = ''): array
    {
        $contents = $kind === 'message' ? ['subject' => '', 'message' => ''] : EmailContent::defaults($kind);
        $fields = [];
        foreach (['subject' => ['email_subject', 'text', $contents['subject']], 'message' => ['email_body', 'textarea', $contents['message']], 'use_greeting' => ['email_greeting', 'checkbox', config('laravelusers.emails.use_greeting', true)], 'greeting' => ['email_greeting_text', 'text', config('laravelusers.emails.greeting', 'Hi')], 'include_name' => ['email_include_name', 'checkbox', config('laravelusers.emails.include_name', true)], 'use_signoff' => ['email_signoff', 'checkbox', config('laravelusers.emails.use_signoff', true)], 'signoff' => ['email_signoff_text', 'text', config('laravelusers.emails.signoff', 'Thanks')], 'signoff_name' => ['email_signoff_name', 'text', config('laravelusers.emails.signoff_name', '')]] as $key => [$label, $type, $value]) {
            $fields[] = $this->field($prefix.$key, __('laravelusers::ui.'.$label), $type, $value, ['section' => 'message', 'required' => in_array($key, ['subject', 'message'], true), 'maxlength' => $key === 'message' ? max(1, (int) config('laravelusers.emails.max_length', 10000)) : ($key === 'subject' ? 150 : 120)]);
        }

        return $fields;
    }

    private function expiryFields(string $prefix, int $minutes, bool $never): array
    {
        $fields = [$this->field($prefix.'_duration', __('laravelusers::ui.reset_duration'), 'number', $minutes, ['min' => 1, 'section' => 'expiry']), $this->field($prefix.'_unit', __('laravelusers::ui.reset_unit'), 'select', 'minutes', ['options' => $this->options(['minutes', 'hours', 'days'], 'reset_'), 'section' => 'expiry'])];
        if ($never) {
            $fields[] = $this->field($prefix.'_never_expire', __('laravelusers::ui.never_expire'), 'checkbox', false, ['section' => 'expiry']);
        }

        return $fields;
    }

    private function deleteForm(Request $request): array
    {
        $fields = [];
        if (GoodbyeEmail::allowed()) {
            $fields[] = $this->field('send_goodbye', __('laravelusers::ui.goodbye_send'), 'checkbox', config('laravelusers.emails.goodbye_on_delete', false));
            foreach ($this->emailFields('goodbye', 'goodbye.') as $field) {
                $fields[] = $field + ['when' => ['key' => 'send_goodbye', 'equals' => true]];
            }
        }

        $form = $this->form('delete-user', __('laravelusers::ui.delete'), null, 'DELETE', $fields, $request) + ['dialog' => true, 'danger' => true, 'confirm' => config('laravelusers.confirmDelete', true) ? __('laravelusers::ui.confirm_bulk') : null];
        $form['submit'] = __('laravelusers::ui.delete');

        return $form;
    }

    private function settings(array $page, array $data, Request $request): array
    {
        $ready = (bool) ($data['settingsAvailable'] ?? false);
        $fields = [];
        if (UserAccess::allows('edit_appearance')) {
            $fields[] = $this->field('show_breadcrumbs', __('laravelusers::ui.settings_breadcrumbs'), 'checkbox', config('laravelusers.showBreadcrumbs', false), ['help' => __('laravelusers::ui.settings_breadcrumbs_hint'), 'section' => 'appearance']);
            $fields[] = $this->field('avatar_source', __('laravelusers::ui.avatar_source'), 'select', config('laravelusers.avatar.source', 'initials'), ['options' => $this->options(Avatar::SOURCES, 'avatar_source_'), 'section' => 'appearance']);
            foreach (['profile' => 'profileCard', 'edit' => 'editCard', 'profile_dark' => 'profileCardDark', 'edit_dark' => 'editCardDark'] as $kind => $prefix) {
                $fallback = str_replace('Dark', '', $prefix);
                $fields[] = $this->field($kind.'_color', __('laravelusers::ui.settings_'.$kind.'_color'), 'color', Frontend::colors(config('laravelusers.'.$prefix.'Color') ?? config('laravelusers.'.$fallback.'Color'))['base'], ['section' => 'appearance']);
                $fields[] = $this->field($kind.'_gradient', __('laravelusers::ui.appearance_gradient'), 'checkbox', config('laravelusers.'.$prefix.'Gradient') ?? config('laravelusers.'.$fallback.'Gradient', true), ['section' => 'appearance']);
                $fields[] = $this->field($kind.'_gradient_strength', __('laravelusers::ui.gradient_strength'), 'range', config('laravelusers.'.$prefix.'GradientStrength') ?? config('laravelusers.'.$fallback.'GradientStrength', 50), ['min' => 0, 'max' => 100, 'section' => 'appearance']);
                $dark = str_ends_with($kind, '_dark');
                $fields[] = $this->field($kind.'_gradient_highlight_color', __('laravelusers::ui.gradient_highlight_color'), 'color', config('laravelusers.'.$prefix.'GradientHighlightColor', $dark ? null : '#ffffff'), ['nullable' => $dark, 'fallback' => config('laravelusers.'.$fallback.'GradientHighlightColor', '#ffffff'), 'inherit_from' => $dark ? str_replace('_dark', '', $kind).'_gradient_highlight_color' : null, 'inherit_label' => __('laravelusers::ui.appearance_inherit_highlight_color'), 'section' => 'appearance']);
            }
            if (Route::has('users.settings.avatar-preview')) {
                $avatars = [];
                foreach (['profile', 'edit', 'profile_dark', 'edit_dark'] as $kind) {
                    $sample = $data['appearancePreviewAvatars'][$kind] ?? null;
                    if (is_array($sample)) {
                        $avatars[$kind] = ['name' => $sample['name'] ?? '', 'avatar' => is_array($sample['avatar'] ?? null) ? Arr::only($sample['avatar'], ['src', 'initials', 'fallback', 'size']) : null];
                    }
                }
                $page['data']['appearance_preview'] = ['url' => route('users.settings.avatar-preview'), 'avatars' => $avatars];
            }
        }
        if (UserAccess::allows('edit_notifications')) {
            $fields[] = $this->field('notifications_driver', __('laravelusers::ui.settings_notification_style'), 'select', config('laravelusers.notifications.driver', 'alert'), ['options' => $this->options(UserNotifications::toastInstalled() ? ['alert', 'toast', 'both'] : ['alert'], 'settings_notification_'), 'section' => 'notifications']);
            $fields[] = $this->field('notifications_dismissible', __('laravelusers::ui.settings_dismissible'), 'checkbox', config('laravelusers.notifications.dismissible', true), ['section' => 'notifications']);
            if (UserNotifications::toastInstalled()) {
                $values = ToastSettings::values();
                foreach (ToastSettings::fields() as $key => $field) {
                    $attributes = Arr::only($field, ['min', 'max', 'step']) + ['section' => 'notifications', 'when' => ['key' => 'notifications_driver', 'in' => ['toast', 'both']]];
                    if (isset($field['options'])) {
                        $attributes['options'] = array_map(fn ($value) => ['value' => $value, 'label' => ucwords(str_replace(['-', '_'], ' ', $value))], $field['options']);
                    }
                    $fields[] = $this->field('toast.'.$key, __('laravelusers::ui.toast_'.$key), $field['type'], $values[$key], $attributes);
                }
            }

        }
        if ($data['accessAvailable'] ?? false) {
            foreach (UserAccess::ACTIONS as $action) {
                $rule = config('laravelusers.access.'.$action, ['mode' => 'inherit']);
                $fields[] = $this->field('access.'.$action.'.mode', __('laravelusers::ui.access_'.$action), 'select', $rule['mode'] ?? 'inherit', ['options' => $this->options(['inherit', 'restricted', 'deny'], 'settings_mode_'), 'section' => 'access']);
                foreach (['roles', 'permissions'] as $kind) {
                    $fields[] = $this->field('access.'.$action.'.'.$kind, __('laravelusers::ui.settings_'.$kind), 'select', array_map('strval', $rule[$kind] ?? []), ['multiple' => true, 'options' => $this->choices($data[$kind] ?? []), 'section' => 'access']);
                }
                if ($data['levelsAvailable'] ?? false) {
                    $fields[] = $this->field('access.'.$action.'.level', __('laravelusers::ui.settings_level'), 'number', $rule['level'] ?? '', ['min' => 1, 'max' => 100000, 'section' => 'access']);
                }
            }
        }
        $page['forms']['settings'] = $this->form('settings', $page['title'], route('users.settings.update'), 'PUT', $fields, $request) + ['disabled' => !$ready, 'tabs' => true, 'help' => !$ready ? __('laravelusers::ui.settings_migration_required') : null];
        $page['data']['form_ids'][] = 'settings';
        $page = $this->cleanupSettings($page, $ready, $request);
        $page = $this->emailSettings($page, $ready, $request);
        $page = $this->accountSettings($page, $ready, $request);

        return $this->packageSettings($page, $data, $request);
    }

    private function cleanupSettings(array $page, bool $ready, Request $request): array
    {
        if (!config('laravelusers.softDeletedEnabled', false) || !UserAccess::allows('edit_cleanup') || !UserAccess::allows('force_delete')) {
            return $page;
        }
        $fields = [$this->field('enabled', __('laravelusers::ui.cleanup_enable'), 'checkbox', config('laravelusers.cleanup.enabled', false)), $this->field('amount', __('laravelusers::ui.cleanup_retention'), 'number', config('laravelusers.cleanup.amount', 180), ['min' => 1, 'max' => 10000]), $this->field('unit', __('laravelusers::ui.cleanup_unit'), 'select', config('laravelusers.cleanup.unit', 'days'), ['options' => $this->options(['immediately', 'minutes', 'hours', 'days', 'months', 'years'], 'cleanup_')]), $this->field('confirmation', __('laravelusers::ui.cleanup_confirmation'), 'text', '', ['required_text' => 'permanently delete', 'when' => ['key' => 'enabled', 'equals' => true]])];
        $page['forms']['cleanup'] = $this->form('cleanup', __('laravelusers::ui.cleanup_title'), route('users.settings.cleanup'), 'PUT', $fields, $request) + ['disabled' => !$ready, 'help' => __('laravelusers::ui.cleanup_warning'), 'danger' => true];
        $page['data']['form_ids'][] = 'cleanup';

        return $page;
    }

    private function emailSettings(array $page, bool $ready, Request $request): array
    {
        if (!UserAccess::allows('edit_email_templates')) {
            return $page;
        }
        $fields = [$this->field('welcome_enabled', __('laravelusers::ui.email_welcome_enabled'), 'checkbox', config('laravelusers.welcome.enabled', false), ['disabled' => !config('laravelusers.emails.enabled', false) || !config('laravelusers.emails.welcome', true)]), $this->field('goodbye', __('laravelusers::ui.goodbye_enable'), 'checkbox', config('laravelusers.emails.goodbye', false)), $this->field('goodbye_on_delete', __('laravelusers::ui.goodbye_default'), 'checkbox', config('laravelusers.emails.goodbye_on_delete', false))];
        foreach (['auto_send', 'restore', 'force_delete', 'retention', 'show_expiry'] as $key) {
            $fields[] = $this->field('goodbye_'.$key, __('laravelusers::ui.goodbye_'.$key), 'checkbox', config('laravelusers.emails.goodbye_'.$key, $key === 'show_expiry'), ['disabled' => in_array($key, ['restore', 'force_delete'], true) && (!config('laravelusers.softDeletedEnabled', false) || !config('laravelusers.account_links.enabled', false) || !config('laravelusers.account_links.'.$key, true))]);
        }
        $modes = ['custom'];
        if (config('laravelusers.cleanup.enabled', false)) {
            $modes[] = 'cleanup';
        }
        if (config('laravelusers.account_links.allow_never_expire', true)) {
            $modes[] = 'never';
        }
        $fields[] = $this->field('goodbye_expiry_mode', __('laravelusers::ui.goodbye_expiry_mode'), 'select', config('laravelusers.emails.goodbye_expiry_mode', 'custom'), ['options' => $this->options($modes, 'goodbye_expiry_')]);
        $fields[] = $this->field('goodbye_duration', __('laravelusers::ui.email_duration'), 'number', config('laravelusers.emails.goodbye_duration', 60), ['min' => 1, 'max' => 525600]);
        $fields[] = $this->field('goodbye_unit', __('laravelusers::ui.email_duration_unit'), 'select', config('laravelusers.emails.goodbye_unit', 'minutes'), ['options' => $this->options(['minutes', 'hours', 'days'], 'cleanup_')]);
        foreach (['welcome', 'reset', 'restore', 'force_delete', 'goodbye'] as $kind) {
            $contents = EmailContent::defaults($kind);
            $fields[] = $this->field('templates.'.$kind.'.subject', __('laravelusers::ui.email_subject'), 'text', $contents['subject'], ['section' => 'email-'.$kind, 'required' => true, 'maxlength' => 150]);
            $fields[] = $this->field('templates.'.$kind.'.message', __('laravelusers::ui.email_body'), 'textarea', $contents['message'], ['section' => 'email-'.$kind, 'required' => true, 'maxlength' => max(1, (int) config('laravelusers.emails.max_length', 10000))]);
        }
        $page['forms']['email-templates'] = $this->form('email-templates', __('laravelusers::ui.email_templates_title'), route('users.settings.emails'), 'PUT', $fields, $request) + ['disabled' => !$ready, 'accordion' => true];
        $page['data']['form_ids'][] = 'email-templates';

        return $page;
    }

    private function accountSettings(array $page, bool $ready, Request $request): array
    {
        if (!UserAccess::allows('edit_account_access')) {
            return $page;
        }
        $available = $request->user() instanceof Model && AccountPreferences::available($request->user());
        $fields = [];
        foreach (['enabled', 'settings_enabled'] as $key) {
            $fields[] = $this->field($key, __('laravelusers::ui.account_'.$key), 'checkbox', config('laravelusers.account.'.$key, false));
        }
        $page['forms']['accounts'] = $this->form('accounts', __('laravelusers::ui.account_access_title'), route('users.settings.accounts'), 'PUT', $fields, $request) + ['disabled' => !$ready || !$available, 'help' => !$available ? __('laravelusers::ui.account_migration_required') : __('laravelusers::ui.account_access_hint')];
        $page['data']['form_ids'][] = 'accounts';
        foreach (['enabled', 'settings_enabled'] as $key) {
            $id = 'accounts-apply-'.$key;
            $page['forms'][$id] = $this->form($id, __('laravelusers::ui.account_apply_all'), route('users.settings.accounts'), 'PUT', array_merge($fields, [$this->field('apply_all', '', 'hidden', $key), $this->field('confirmation', __('laravelusers::ui.account_apply_confirmation'), 'text', '', ['required_text' => 'change'])]), $request) + ['dialog' => true, 'disabled' => !$ready || !$available, 'help' => __('laravelusers::ui.account_apply_warning')];
            $page['data']['settings_actions'][] = ['name' => $id, 'label' => __('laravelusers::ui.account_apply_all').' ('.__('laravelusers::ui.account_'.$key).')', 'form' => $id, 'values_from' => 'accounts'];
        }

        return $page;
    }

    private function packageSettings(array $page, array $data, Request $request): array
    {
        if (!($data['packageManagementAllowed'] ?? false)) {
            return $page;
        }
        $page['data']['packages'] = ['installed' => $data['managedPackages'] ?? [], 'ready' => (bool) ($data['packageQueueReady'] ?? false), 'verify' => route('users.settings.packages.verify'), 'status_url' => route('users.settings.packages.status', ['id' => '__JOB_ID__'])];
        $page['data']['packages']['requirements'] = is_array($data['packageRequirements'] ?? null) ? Arr::only($data['packageRequirements'], ['status', 'queue_ready', 'message']) : null;
        $page['data']['packages']['choices'] = [];
        $docs = 'https://laravel.com/docs/'.explode('.', Application::VERSION)[0].'.x';
        $page['data']['packages']['help'] = [['label' => __('laravelusers::ui.packages_queue_setup'), 'url' => $docs.'/queues#introduction'], ['label' => __('laravelusers::ui.packages_worker_setup'), 'url' => $docs.'/queues#running-the-queue-worker'], ['label' => __('laravelusers::ui.packages_cache_setup'), 'url' => $docs.'/cache#atomic-locks']];
        $page['forms']['package-verify'] = $this->form('package-verify', __('laravelusers::ui.package_requirements'), route('users.settings.packages.verify'), 'POST', [$this->field('package', '', 'hidden', 'requirements'), $this->field('operation', '', 'hidden', 'verify')], $request) + ['async' => true, 'verify' => true];
        $page['forms']['package-verify']['submit'] = __('laravelusers::ui.package_requirements_verify');
        $page['data']['package_operation'] = is_array($data['packageOperation'] ?? null) ? Arr::only($data['packageOperation'], ['id', 'status_url', 'status', 'stage', 'package', 'operation', 'message', 'queued_at', 'started_at', 'updated_at']) : null;
        foreach (['requirements' => 'Package requirements', 'toast' => 'Laravel Toast', 'laravel-roles' => 'Laravel Roles', 'spatie' => 'Spatie Permissions'] as $package => $label) {
            $installed = (bool) ($data['managedPackages'][$package] ?? false);
            $operation = $package === 'requirements' ? 'setup' : ($installed ? 'remove' : 'install');
            $word = $operation === 'remove' ? 'remove' : 'continue';
            $fields = [$this->field('package', '', 'hidden', $package), $this->field('operation', '', 'hidden', $operation), $this->field('setup', __('laravelusers::ui.package_publish_missing'), 'checkbox', true), $this->field('migrate', __('laravelusers::ui.package_run_migrations'), 'checkbox', false), $this->field('acknowledgement', __('laravelusers::ui.packages_acknowledgement'), 'checkbox', false, ['required' => true]), $this->field('confirmation', __('laravelusers::ui.package_confirmation').' '.$word, 'text', '', ['required_text' => $word])];
            if ($package === 'toast') {
                $fields = array_values(array_filter($fields, fn ($field) => !in_array($field['key'], ['setup', 'migrate'], true)));
            }
            $id = 'package-'.$package;
            $conflict = !$installed && in_array($package, ['laravel-roles', 'spatie'], true) && (($data['managedPackages']['laravel-roles'] ?? false) || ($data['managedPackages']['spatie'] ?? false));
            $unsupported = !$installed && $package === 'toast' && (PHP_VERSION_ID < 80200 || version_compare(Application::VERSION, '10.0.0', '<'));
            $requiresQueue = $package !== 'requirements';
            $blocked = $conflict || $unsupported;
            $disabled = $blocked || ($requiresQueue && !($data['packageQueueReady'] ?? false));
            $setupCompleted = $package === 'toast' && $installed && (bool) ($data['managedPackageSetup']['toast'] ?? false);
            $page['forms'][$id] = $this->form($id, $label, route('users.settings.packages'), 'POST', $fields, $request) + ['dialog' => true, 'async' => true, 'disabled' => $disabled, 'blocked' => $blocked, 'requires_queue' => $requiresQueue, 'danger' => $operation === 'remove', 'help' => $operation === 'remove' ? __('laravelusers::ui.packages_remove_roles_warning') : __('laravelusers::ui.packages_acknowledgement')];
            $page['data']['settings_actions'][] = ['name' => $id, 'label' => $label.' ('.$operation.')', 'form' => $id, 'disabled' => $disabled || ($package === 'requirements' && ($data['packageQueueReady'] ?? false))];
            if ($package !== 'requirements') {
                $page['data']['packages']['choices'][] = ['name' => $id, 'package' => $package, 'label' => $label, 'installed' => $installed, 'operation' => $operation, 'blocked' => $blocked, 'disabled' => $disabled, 'reason' => $conflict ? __('laravelusers::ui.packages_roles_conflict') : ($unsupported ? __('laravelusers::ui.packages_toast_unsupported') : null), 'hint' => $package === 'toast' ? __('laravelusers::ui.packages_toast_hint') : null, 'setup_hint' => $installed && !$setupCompleted ? __('laravelusers::ui.'.($package === 'toast' ? 'package_toast_managed_setup' : 'package_roles_setup')) : null, 'setup_completed' => $setupCompleted, 'configure_name' => $installed && !$setupCompleted ? $id.'-configure' : null];
            }
            if ($installed && !$setupCompleted && $package !== 'requirements') {
                $configureId = $id.'-configure';
                $configureFields = [$this->field('package', '', 'hidden', $package), $this->field('operation', '', 'hidden', 'configure')];
                if ($package !== 'toast') {
                    $configureFields[] = $this->field('migrate', __('laravelusers::ui.package_run_migrations'), 'checkbox', false);
                }
                $configureFields[] = $this->field('acknowledgement', __('laravelusers::ui.packages_acknowledgement'), 'checkbox', false, ['required' => true]);
                $configureFields[] = $this->field('confirmation', __('laravelusers::ui.package_confirmation').' continue', 'text', '', ['required_text' => 'continue']);
                $page['forms'][$configureId] = $this->form($configureId, __('laravelusers::ui.package_configure').' '.$label, route('users.settings.packages'), 'POST', $configureFields, $request) + ['dialog' => true, 'async' => true, 'disabled' => $disabled, 'blocked' => false, 'requires_queue' => true];
                $page['data']['settings_actions'][] = ['name' => $configureId, 'label' => __('laravelusers::ui.package_configure'), 'form' => $configureId, 'disabled' => $disabled];
            }
        }
        if ($data['accessAvailable'] ?? false) {
            $page['forms']['impersonation-settings'] = $this->form('impersonation-settings', __('laravelusers::ui.impersonation'), route('users.settings.impersonation'), 'POST', [$this->field('enabled', __('laravelusers::ui.impersonation'), 'checkbox', (bool) ($data['impersonationEnabled'] ?? false))], $request) + ['disabled' => !($data['settingsAvailable'] ?? false)];
            $page['data']['form_ids'][] = 'impersonation-settings';
        }

        return $page;
    }

    private function account(array $page, array $data, Request $request): array
    {
        $user = $data['user'];
        $page['data']['user'] = $this->identity($user) + ['avatar' => $this->avatars->forUser($user), 'appearance' => AppearancePreferences::colors([$user])[$user->getKey()] ?? null, 'full_name' => $data['fullName'] ?? null, 'pending_email' => ($data['pendingEmail'] ?? null)?->new_email, 'editable' => (bool) ($data['accountEditable'] ?? false)];
        if (!$page['data']['user']['editable']) {
            $page['data']['notice'] = __('laravelusers::ui.account_readonly');

            return $page;
        }
        $sections = [];
        if (config('laravelusers.account.profile', true)) {
            $sections['profile'] = [$this->field('username', __('laravelusers::ui.account_username'), 'text', $user->getAttribute(config('laravelusers.account.username_column', 'name')), ['required' => true]), $this->field('full_name', __('laravelusers::ui.account_name'), 'text', ($data['fullName'] ?? null) ?: $user->name, ['required' => true])];
        }
        if (config('laravelusers.account.avatar', true) || config('laravelusers.account.appearance', true)) {
            $sections['appearance'] = $this->appearanceFields($data, (bool) config('laravelusers.account.avatar', true), (bool) config('laravelusers.account.appearance', true));
        }
        foreach (['email' => [$this->field('email', __('laravelusers::ui.account_new_email'), 'email', $user->email, ['required' => true]), $this->field('current_password', __('laravelusers::ui.account_current_password'), 'password', '', ['required' => true])], 'password' => [$this->field('current_password', __('laravelusers::ui.account_current_password'), 'password', '', ['required' => true]), $this->field('password', __('laravelusers::ui.account_new_password'), 'password', '', ['required' => true]), $this->field('password_confirmation', __('laravelusers::forms.create_user_label_pw_confirmation'), 'password', '', ['required' => true])]] as $section => $fields) {
            if (config('laravelusers.account.'.$section, true)) {
                $sections[$section] = $fields;
            }
        }
        foreach ($sections as $section => $fields) {
            $id = 'account-'.$section;
            $page['forms'][$id] = $this->form($id, __('laravelusers::ui.account_'.($section === 'appearance' ? 'tab_appearance' : $section)), route('users.account.update'), 'PUT', array_merge([$this->field('section', '', 'hidden', $section)], $fields), $request);
            $page['data']['form_ids'][] = $id;
        }
        if (config('laravelusers.account.delete', true)) {
            $page['forms']['account-delete'] = $this->form('account-delete', __('laravelusers::ui.account_delete'), route('users.account.delete'), 'DELETE', [$this->field('current_password', __('laravelusers::ui.account_current_password'), 'password', '', ['required' => true]), $this->field('confirmation', __('laravelusers::ui.account_delete_confirm'), 'text', '', ['required_text' => 'delete'])], $request) + ['dialog' => true, 'danger' => true, 'help' => __('laravelusers::ui.account_delete_warning')];
            $page['data']['settings_actions'][] = ['name' => 'account-delete', 'label' => __('laravelusers::ui.account_delete'), 'form' => 'account-delete', 'class' => 'lu-danger'];
        }

        return $page;
    }

    private function confirmation(array $page, array $data, Request $request): array
    {
        $email = $page['screen'] === 'confirm-email';
        $valid = $email ? (bool) ($data['change'] ?? false) : (bool) ($data['link'] ?? false);
        $completed = $data['completed'] ?? null;
        if ($valid) {
            $action = $email ? 'email' : $data['link']->action;
            $page['data']['notice'] = __('laravelusers::ui.'.($email ? 'account_email_both' : 'account_'.$action.'_confirm'));
            $page['forms']['confirmation'] = $this->form('confirmation', $email ? $page['title'] : __('laravelusers::ui.account_'.$action), route($email ? 'users.account.email.accept' : 'users.account-link.confirm', ['token' => $data['token']]), 'POST', [], $request) + ['danger' => $action === 'force_delete'];
            $page['forms']['confirmation']['submit'] = $page['forms']['confirmation']['title'];
            $page['data']['form_ids'] = ['confirmation'];
        } else {
            $key = $email ? ($completed === 'complete' ? 'account_email_complete' : ($completed === 'pending' ? 'account_email_waiting' : 'account_email_invalid')) : ($completed ? 'account_'.$completed.'_done' : 'account_link_invalid_help');
            $page['data']['notice'] = __('laravelusers::ui.'.$key);
        }
        $page['data']['valid'] = $valid;
        $page['data']['completed'] = $completed;

        return $page;
    }
}
