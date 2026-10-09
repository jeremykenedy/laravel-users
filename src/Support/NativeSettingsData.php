<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

/**
 * Composes independent serializers for the authorized screen sections.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class NativeSettingsData
{
    public function __construct(private NativeFormData $forms, private NativePackageForms $packages)
    {
    }

    public function settings(array $page, array $data, Request $request): array
    {
        $ready = (bool) ($data['settingsAvailable'] ?? false);
        $fields = [];
        [$page, $fields] = $this->appearanceSettings($page, $data);
        $fields = array_merge($fields, $this->notificationFields(), $this->accessFields($data));
        $page['forms']['settings'] = $this->forms->form('settings', $page['title'], route('users.settings.update'), 'PUT', $fields, $request) + ['disabled' => !$ready, 'tabs' => true, 'help' => !$ready ? __('laravelusers::ui.settings_migration_required') : null];
        $page['data']['form_ids'][] = 'settings';
        $page = $this->cleanupSettings($page, $ready, $request);
        $page = $this->emailSettings($page, $ready, $request);
        $page = $this->accountSettings($page, $ready, $request);

        return $this->packages->settings($page, $data, $request);
    }

    private function cleanupSettings(array $page, bool $ready, Request $request): array
    {
        if (!config('laravelusers.softDeletedEnabled', false) || !UserAccess::allows('edit_cleanup') || !UserAccess::allows('force_delete')) {
            return $page;
        }
        $fields = [$this->forms->field('enabled', __('laravelusers::ui.cleanup_enable'), 'checkbox', config('laravelusers.cleanup.enabled', false)), $this->forms->field('amount', __('laravelusers::ui.cleanup_retention'), 'number', config('laravelusers.cleanup.amount', 180), ['min' => 1, 'max' => 10000]), $this->forms->field('unit', __('laravelusers::ui.cleanup_unit'), 'select', config('laravelusers.cleanup.unit', 'days'), ['options' => $this->forms->options(['immediately', 'minutes', 'hours', 'days', 'months', 'years'], 'cleanup_')]), $this->forms->field('confirmation', __('laravelusers::ui.cleanup_confirmation'), 'text', '', ['required_text' => 'permanently delete', 'when' => ['key' => 'enabled', 'equals' => true]])];
        $page['forms']['cleanup'] = $this->forms->form('cleanup', __('laravelusers::ui.cleanup_title'), route('users.settings.cleanup'), 'PUT', $fields, $request) + ['disabled' => !$ready, 'help' => __('laravelusers::ui.cleanup_warning'), 'danger' => true];
        $page['data']['form_ids'][] = 'cleanup';

        return $page;
    }

    private function emailSettings(array $page, bool $ready, Request $request): array
    {
        if (!UserAccess::allows('edit_email_templates')) {
            return $page;
        }
        $fields = [$this->forms->field('welcome_enabled', __('laravelusers::ui.email_welcome_enabled'), 'checkbox', config('laravelusers.welcome.enabled', false), ['disabled' => !config('laravelusers.emails.enabled', false) || !config('laravelusers.emails.welcome', true)]), $this->forms->field('goodbye', __('laravelusers::ui.goodbye_enable'), 'checkbox', config('laravelusers.emails.goodbye', false)), $this->forms->field('goodbye_on_delete', __('laravelusers::ui.goodbye_default'), 'checkbox', config('laravelusers.emails.goodbye_on_delete', false))];
        foreach (['auto_send', 'restore', 'force_delete', 'retention', 'show_expiry'] as $key) {
            $fields[] = $this->forms->field('goodbye_'.$key, __('laravelusers::ui.goodbye_'.$key), 'checkbox', config('laravelusers.emails.goodbye_'.$key, $key === 'show_expiry'), ['disabled' => in_array($key, ['restore', 'force_delete'], true) && (!config('laravelusers.softDeletedEnabled', false) || !config('laravelusers.account_links.enabled', false) || !config('laravelusers.account_links.'.$key, true))]);
        }
        $modes = $this->goodbyeExpiryModes();
        $fields[] = $this->forms->field('goodbye_expiry_mode', __('laravelusers::ui.goodbye_expiry_mode'), 'select', config('laravelusers.emails.goodbye_expiry_mode', 'custom'), ['options' => $this->forms->options($modes, 'goodbye_expiry_')]);
        $fields[] = $this->forms->field('goodbye_duration', __('laravelusers::ui.email_duration'), 'number', config('laravelusers.emails.goodbye_duration', 60), ['min' => 1, 'max' => 525600]);
        $fields[] = $this->forms->field('goodbye_unit', __('laravelusers::ui.email_duration_unit'), 'select', config('laravelusers.emails.goodbye_unit', 'minutes'), ['options' => $this->forms->options(['minutes', 'hours', 'days'], 'cleanup_')]);
        foreach (['welcome', 'reset', 'restore', 'force_delete', 'goodbye'] as $kind) {
            $contents = EmailContent::defaults($kind);
            $fields[] = $this->forms->field('templates.'.$kind.'.subject', __('laravelusers::ui.email_subject'), 'text', $contents['subject'], ['section' => 'email-'.$kind, 'required' => true, 'maxlength' => 150]);
            $fields[] = $this->forms->field('templates.'.$kind.'.message', __('laravelusers::ui.email_body'), 'textarea', $contents['message'], ['section' => 'email-'.$kind, 'required' => true, 'maxlength' => max(1, (int) config('laravelusers.emails.max_length', 10000))]);
        }
        $page['forms']['email-templates'] = $this->forms->form('email-templates', __('laravelusers::ui.email_templates_title'), route('users.settings.emails'), 'PUT', $fields, $request) + ['disabled' => !$ready, 'accordion' => true];
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
            $fields[] = $this->forms->field($key, __('laravelusers::ui.account_'.$key), 'checkbox', config('laravelusers.account.'.$key, false));
        }
        $page['forms']['accounts'] = $this->forms->form('accounts', __('laravelusers::ui.account_access_title'), route('users.settings.accounts'), 'PUT', $fields, $request) + ['disabled' => !$ready || !$available, 'help' => !$available ? __('laravelusers::ui.account_migration_required') : __('laravelusers::ui.account_access_hint')];
        $page['data']['form_ids'][] = 'accounts';
        foreach (['enabled', 'settings_enabled'] as $key) {
            $id = 'accounts-apply-'.$key;
            $page['forms'][$id] = $this->forms->form($id, __('laravelusers::ui.account_apply_all'), route('users.settings.accounts'), 'PUT', array_merge($fields, [$this->forms->field('apply_all', '', 'hidden', $key), $this->forms->field('confirmation', __('laravelusers::ui.account_apply_confirmation'), 'text', '', ['required_text' => 'change'])]), $request) + ['dialog' => true, 'disabled' => !$ready || !$available, 'help' => __('laravelusers::ui.account_apply_warning')];
            $page['data']['settings_actions'][] = ['name' => $id, 'label' => __('laravelusers::ui.account_apply_all').' ('.__('laravelusers::ui.account_'.$key).')', 'form' => $id, 'values_from' => 'accounts'];
        }

        return $page;
    }

    private function appearanceSettings(array $page, array $data): array
    {
        $fields = [];
        if (UserAccess::allows('edit_appearance')) {
            $fields[] = $this->forms->field('show_breadcrumbs', __('laravelusers::ui.settings_breadcrumbs'), 'checkbox', config('laravelusers.showBreadcrumbs', false), ['help' => __('laravelusers::ui.settings_breadcrumbs_hint'), 'section' => 'appearance']);
            $fields[] = $this->forms->field('avatar_source', __('laravelusers::ui.avatar_source'), 'select', config('laravelusers.avatar.source', 'initials'), ['options' => $this->forms->options(Avatar::SOURCES, 'avatar_source_'), 'section' => 'appearance']);
            foreach (['profile' => 'profileCard', 'edit' => 'editCard', 'profile_dark' => 'profileCardDark', 'edit_dark' => 'editCardDark'] as $kind => $prefix) {
                $fallback = str_replace('Dark', '', $prefix);
                $fields[] = $this->forms->field($kind.'_color', __('laravelusers::ui.settings_'.$kind.'_color'), 'color', Frontend::colors(config('laravelusers.'.$prefix.'Color') ?? config('laravelusers.'.$fallback.'Color'))['base'], ['section' => 'appearance']);
                $fields[] = $this->forms->field($kind.'_gradient', __('laravelusers::ui.appearance_gradient'), 'checkbox', config('laravelusers.'.$prefix.'Gradient') ?? config('laravelusers.'.$fallback.'Gradient', true), ['section' => 'appearance']);
                $fields[] = $this->forms->field($kind.'_gradient_strength', __('laravelusers::ui.gradient_strength'), 'range', config('laravelusers.'.$prefix.'GradientStrength') ?? config('laravelusers.'.$fallback.'GradientStrength', 50), ['min' => 0, 'max' => 100, 'section' => 'appearance']);
                $dark = str_ends_with($kind, '_dark');
                $fields[] = $this->forms->field($kind.'_gradient_highlight_color', __('laravelusers::ui.gradient_highlight_color'), 'color', config('laravelusers.'.$prefix.'GradientHighlightColor', $dark ? null : '#ffffff'), ['nullable' => $dark, 'fallback' => config('laravelusers.'.$fallback.'GradientHighlightColor', '#ffffff'), 'inherit_from' => $dark ? str_replace('_dark', '', $kind).'_gradient_highlight_color' : null, 'inherit_label' => __('laravelusers::ui.appearance_inherit_highlight_color'), 'section' => 'appearance']);
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

        return [$page, $fields];
    }

    private function notificationFields(): array
    {
        $fields = [];
        if (UserAccess::allows('edit_notifications')) {
            $fields[] = $this->forms->field('notifications_driver', __('laravelusers::ui.settings_notification_style'), 'select', config('laravelusers.notifications.driver', 'alert'), ['options' => $this->forms->options(UserNotifications::toastInstalled() ? ['alert', 'toast', 'both'] : ['alert'], 'settings_notification_'), 'section' => 'notifications']);
            $fields[] = $this->forms->field('notifications_dismissible', __('laravelusers::ui.settings_dismissible'), 'checkbox', config('laravelusers.notifications.dismissible', true), ['section' => 'notifications']);
            if (UserNotifications::toastInstalled()) {
                $values = ToastSettings::values();
                foreach (ToastSettings::fields() as $key => $field) {
                    $attributes = Arr::only($field, ['min', 'max', 'step']) + ['section' => 'notifications', 'when' => ['key' => 'notifications_driver', 'in' => ['toast', 'both']]];
                    if (isset($field['options'])) {
                        $attributes['options'] = array_map(fn ($value) => ['value' => $value, 'label' => ucwords(str_replace(['-', '_'], ' ', $value))], $field['options']);
                    }
                    $fields[] = $this->forms->field('toast.'.$key, __('laravelusers::ui.toast_'.$key), $field['type'], $values[$key], $attributes);
                }
            }
        }

        return $fields;
    }

    private function accessFields(array $data): array
    {
        $fields = [];
        if ($data['accessAvailable'] ?? false) {
            foreach (UserAccess::ACTIONS as $action) {
                $rule = config('laravelusers.access.'.$action, ['mode' => 'inherit']);
                $fields[] = $this->forms->field('access.'.$action.'.mode', __('laravelusers::ui.access_'.$action), 'select', $rule['mode'] ?? 'inherit', ['options' => $this->forms->options(['inherit', 'restricted', 'deny'], 'settings_mode_'), 'section' => 'access']);
                foreach (['roles', 'permissions'] as $kind) {
                    $fields[] = $this->forms->field('access.'.$action.'.'.$kind, __('laravelusers::ui.settings_'.$kind), 'select', array_map('strval', $rule[$kind] ?? []), ['multiple' => true, 'options' => $this->forms->choices($data[$kind] ?? []), 'section' => 'access']);
                }
                if ($data['levelsAvailable'] ?? false) {
                    $fields[] = $this->forms->field('access.'.$action.'.level', __('laravelusers::ui.settings_level'), 'number', $rule['level'] ?? '', ['min' => 1, 'max' => 100000, 'section' => 'access']);
                }
            }
        }

        return $fields;
    }

    private function goodbyeExpiryModes(): array
    {
        $modes = ['custom'];
        if (config('laravelusers.cleanup.enabled', false)) {
            $modes[] = 'cleanup';
        }
        if (config('laravelusers.account_links.allow_never_expire', true)) {
            $modes[] = 'never';
        }

        return $modes;
    }
}
