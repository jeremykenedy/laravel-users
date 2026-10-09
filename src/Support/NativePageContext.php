<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;

class NativePageContext
{
    public function __construct(private NativeNavigation $navigation)
    {
    }

    public function base(string $view, array $data, Request $request): array
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
            'urls'         => $this->navigation->urls($public, $request, $screen),
            'features'     => $this->features($screen),
            'capabilities' => $capabilities,
            'labels'       => $this->labels(),
            'data'         => ['navigation' => $this->navigation->navigation($request, $public), 'form_ids' => [], 'home' => Frontend::homeUrl()],
            'forms'        => [],
            'flash'        => UserNotifications::useAlerts() ? $this->flash($request) : [],
        ];
        $page['data']['toasts'] = $request->hasSession() ? UserNotifications::toasts($request->session()) : [];

        return $page;
    }

    private function screen(string $view): string
    {
        if (in_array($view, ['laravelusers::account.page', 'laravelusers::account.confirm-email', 'laravelusers::account-links.confirm'], true)) {
            return ['laravelusers::account.page' => 'account', 'laravelusers::account.confirm-email' => 'confirm-email', 'laravelusers::account-links.confirm' => 'account-link'][$view];
        }
        $name = substr($view, strrpos($view, '.') + 1);
        if (!isset(NativePageData::SCREENS[$name]) || !preg_match('/\Alaravelusers::(?:usersmanagement|modern)\./', $view)) {
            throw new InvalidArgumentException('The selected view does not have a native screen.');
        }

        return NativePageData::SCREENS[$name];
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
}
