<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NativeAccountForms
{
    public function __construct(private NativeFormData $forms, private NativeUserData $users, private NativeAppearanceData $appearance, private Avatar $avatars)
    {
    }

    public function page(array $page, array $data, Request $request): array
    {
        $user = $data['user'];
        $page['data']['user'] = $this->users->identity($user) + ['avatar' => $this->avatars->forUser($user), 'appearance' => AppearancePreferences::colors([$user])[$user->getKey()] ?? null, 'full_name' => $data['fullName'] ?? null, 'pending_email' => ($data['pendingEmail'] ?? null)?->new_email, 'editable' => (bool) ($data['accountEditable'] ?? false)];
        if (!$page['data']['user']['editable']) {
            $page['data']['notice'] = __('laravelusers::ui.account_readonly');

            return $page;
        }
        $sections = $this->sections($user, $data);
        foreach ($sections as $section => $fields) {
            $id = 'account-'.$section;
            $page['forms'][$id] = $this->forms->form($id, __('laravelusers::ui.account_'.($section === 'appearance' ? 'tab_appearance' : $section)), route('users.account.update'), 'PUT', array_merge([$this->forms->field('section', '', 'hidden', $section)], $fields), $request);
            $page['data']['form_ids'][] = $id;
        }
        if (config('laravelusers.account.delete', true)) {
            $page['forms']['account-delete'] = $this->forms->form('account-delete', __('laravelusers::ui.account_delete'), route('users.account.delete'), 'DELETE', [$this->forms->field('current_password', __('laravelusers::ui.account_current_password'), 'password', '', ['required' => true]), $this->forms->field('confirmation', __('laravelusers::ui.account_delete_confirm'), 'text', '', ['required_text' => 'delete'])], $request) + ['dialog' => true, 'danger' => true, 'help' => __('laravelusers::ui.account_delete_warning')];
            $page['data']['settings_actions'][] = ['name' => 'account-delete', 'label' => __('laravelusers::ui.account_delete'), 'form' => 'account-delete', 'class' => 'lu-danger'];
        }

        return $page;
    }

    private function sections(Model $user, array $data): array
    {
        $sections = [];
        if (config('laravelusers.account.profile', true)) {
            $sections['profile'] = [$this->forms->field('username', __('laravelusers::ui.account_username'), 'text', $user->getAttribute(config('laravelusers.account.username_column', 'name')), ['required' => true]), $this->forms->field('full_name', __('laravelusers::ui.account_name'), 'text', ($data['fullName'] ?? null) ?: $user->name, ['required' => true])];
        }
        if (config('laravelusers.account.avatar', true) || config('laravelusers.account.appearance', true)) {
            $sections['appearance'] = $this->appearance->appearanceFields($data, ['avatar' => (bool) config('laravelusers.account.avatar', true), 'appearance' => (bool) config('laravelusers.account.appearance', true)]);
        }
        foreach (['email' => [$this->forms->field('email', __('laravelusers::ui.account_new_email'), 'email', $user->email, ['required' => true]), $this->forms->field('current_password', __('laravelusers::ui.account_current_password'), 'password', '', ['required' => true])], 'password' => [$this->forms->field('current_password', __('laravelusers::ui.account_current_password'), 'password', '', ['required' => true]), $this->forms->field('password', __('laravelusers::ui.account_new_password'), 'password', '', ['required' => true]), $this->forms->field('password_confirmation', __('laravelusers::forms.create_user_label_pw_confirmation'), 'password', '', ['required' => true])]] as $section => $fields) {
            if (config('laravelusers.account.'.$section, true)) {
                $sections[$section] = $fields;
            }
        }

        return $sections;
    }
}
