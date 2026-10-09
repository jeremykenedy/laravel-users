<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NativeUserForms
{
    public function __construct(private NativeFormData $forms, private NativeUserData $users, private NativeAppearanceData $appearance, private Avatar $avatars)
    {
    }

    public function page(array $page, array $data, Request $request): array
    {
        $user = $data['user'] ?? null;
        $editing = $user instanceof Model;
        $deleted = (bool) ($data['deletedUser'] ?? false);
        $fields = array_merge($this->profileFields($editing ? $user : null, $editing), $this->roleFields($data, $editing), $this->permissionFields($data), $this->appearance->appearanceFields($data), $this->accountFields($data), $this->welcomeFields($editing));
        $form = $this->forms->form('user', $page['title'], $editing ? route($deleted ? 'users.deleted.update' : 'users.update', $user->getKey()) : route('users.store'), $editing ? 'PUT' : 'POST', $fields, $request);
        $form['tabs'] = $editing;
        $form['confirm'] = $editing && config('laravelusers.confirmSave', true) ? __('laravelusers::modals.edit_user__modal_text_confirm_message') : null;
        $page['forms']['user'] = $form;
        $page['data']['form_ids'] = ['user'];
        $page['data']['deleted_user'] = $deleted;
        if ($editing) {
            $page['data']['user'] = $this->users->identity($user) + ['avatar' => $this->avatars->forUser($user), 'appearance' => AppearancePreferences::colors([$user])[$user->getKey()] ?? null];
        }

        return $page;
    }

    private function profileFields(?Model $user, bool $editing): array
    {
        $fields = [$this->forms->field('name', __('laravelusers::forms.create_user_label_username'), 'text', $editing ? $user->name : '', ['required' => true, 'section' => 'profile', 'maxlength' => 255]), $this->forms->field('email', __('laravelusers::forms.create_user_label_email'), 'email', $editing ? $user->email : '', ['required' => true, 'section' => 'profile', 'maxlength' => 255]), $this->forms->field('password', __('laravelusers::forms.create_user_label_password'), 'password', '', ['required' => !$editing, 'section' => 'password', 'help' => $editing ? __('laravelusers::ui.password_hint') : null]), $this->forms->field('password_confirmation', __('laravelusers::forms.create_user_label_pw_confirmation'), 'password', '', ['required' => !$editing, 'section' => 'password'])];

        return $fields;
    }

    private function roleFields(array $data, bool $editing): array
    {
        $fields = [];
        if ($data['rolesEnabled'] ?? false) {
            $fields[] = $this->forms->field('role', __('laravelusers::forms.create_user_label_role'), 'select', $editing ? array_map('strval', $data['currentRole'] ?? []) : '', ['options' => $this->forms->roles($data['roles'] ?? []), 'multiple' => $editing, 'required' => true, 'section' => 'roles']);
        }

        return $fields;
    }

    private function permissionFields(array $data): array
    {
        $fields = [];
        if ($data['permissionsEnabled'] ?? false) {
            $fields[] = $this->forms->field('permissions_present', '', 'hidden', 1);
            $fields[] = $this->forms->field('permissions', __('laravelusers::ui.direct_permissions'), 'select', array_map('strval', $data['currentPermissions'] ?? []), ['options' => $this->forms->choices($data['permissions'] ?? []), 'multiple' => true, 'section' => 'roles']);
        }

        return $fields;
    }

    private function accountFields(array $data): array
    {
        $fields = [];
        if (($data['accountPreferenceAvailable'] ?? false) && UserAccess::allows('edit_account_access')) {
            foreach (['enabled', 'settings_enabled'] as $key) {
                $value = ($data['accountPreference'] ?? null)?->$key;
                $fields[] = $this->forms->field('account_'.$key, __('laravelusers::ui.account_'.$key), 'select', $value === null ? 'inherit' : ($value ? 'on' : 'off'), ['options' => $this->forms->options(['inherit', 'on', 'off'], 'appearance_'), 'section' => 'account']);
            }
        }

        return $fields;
    }

    private function welcomeFields(bool $editing): array
    {
        $fields = [];
        if (!$editing && config('laravelusers.emails.enabled', false) && config('laravelusers.welcome.enabled', false) && UserAccess::email('welcome')) {
            $fields[] = $this->forms->field('send_welcome_email', __('laravelusers::ui.send_welcome'), 'checkbox', false, ['section' => 'welcome']);
            if (config('laravelusers.welcome.force_password_reset', true) && UserAccess::email('reset')) {
                $fields[] = $this->forms->field('force_password_reset', __('laravelusers::ui.force_reset'), 'checkbox', false, ['section' => 'welcome', 'help' => __('laravelusers::ui.reset_hint')]);
            }
        }

        return $fields;
    }
}
