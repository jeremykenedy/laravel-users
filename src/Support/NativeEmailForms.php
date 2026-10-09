<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class NativeEmailForms
{
    public function __construct(private NativeFormData $forms)
    {
    }

    public function actions(bool $deleted): array
    {
        if (!$this->available($deleted)) {
            return [];
        }
        $actions = [];
        foreach ($deleted ? ['message'] : ['message', 'reset', 'welcome'] as $action) {
            if ($this->actionAllowed($action, $deleted)) {
                $actions[] = ['name' => 'email-'.$action, 'label' => __('laravelusers::ui.email_'.$action), 'form' => 'email-'.$action, 'disabled' => $action === 'reset' && !Route::has('password.reset')];
            }
        }

        return $actions;
    }

    public function forms(bool $deleted, Request $request): array
    {
        $forms = [];
        foreach ($this->actions($deleted) as $action) {
            $kind = substr($action['name'], 6);
            $fields = [$this->forms->field('action', '', 'hidden', $kind), $this->forms->field('deleted', '', 'hidden', (int) $deleted), $this->forms->field('email_form', '', 'hidden', 1), $this->forms->field('ids', '', 'hidden', [], ['multiple' => true])];
            if ($kind === 'message' || config('laravelusers.emails.edit_'.$kind, true)) {
                $fields = array_merge($fields, $this->emailFields($kind));
            }
            $fields = array_merge($fields, $this->linkFields($kind, $deleted));
            $forms[$action['form']] = $this->forms->form($action['form'], $action['label'], route('users.email'), 'POST', $fields, $request) + ['dialog' => true, 'preview' => config('laravelusers.emails.preview', true) ? route('users.email.preview') : null];
            $forms[$action['form']]['submit'] = __('laravelusers::ui.email_send');
        }

        return $forms;
    }

    private function emailFields(string $kind, string $prefix = ''): array
    {
        $contents = $kind === 'message' ? ['subject' => '', 'message' => ''] : EmailContent::defaults($kind);
        $fields = [];
        foreach (['subject' => ['email_subject', 'text', $contents['subject']], 'message' => ['email_body', 'textarea', $contents['message']], 'use_greeting' => ['email_greeting', 'checkbox', config('laravelusers.emails.use_greeting', true)], 'greeting' => ['email_greeting_text', 'text', config('laravelusers.emails.greeting', 'Hi')], 'include_name' => ['email_include_name', 'checkbox', config('laravelusers.emails.include_name', true)], 'use_signoff' => ['email_signoff', 'checkbox', config('laravelusers.emails.use_signoff', true)], 'signoff' => ['email_signoff_text', 'text', config('laravelusers.emails.signoff', 'Thanks')], 'signoff_name' => ['email_signoff_name', 'text', config('laravelusers.emails.signoff_name', '')]] as $key => [$label, $type, $value]) {
            $fields[] = $this->forms->field($prefix.$key, __('laravelusers::ui.'.$label), $type, $value, ['section' => 'message', 'required' => in_array($key, ['subject', 'message'], true), 'maxlength' => $key === 'message' ? max(1, (int) config('laravelusers.emails.max_length', 10000)) : ($key === 'subject' ? 150 : 120)]);
        }

        return $fields;
    }

    private function expiryFields(string $prefix, int $minutes, bool $never): array
    {
        $fields = [$this->forms->field($prefix.'_duration', __('laravelusers::ui.reset_duration'), 'number', $minutes, ['min' => 1, 'section' => 'expiry']), $this->forms->field($prefix.'_unit', __('laravelusers::ui.reset_unit'), 'select', 'minutes', ['options' => $this->forms->options(['minutes', 'hours', 'days'], 'reset_'), 'section' => 'expiry'])];
        if ($never) {
            $fields[] = $this->forms->field($prefix.'_never_expire', __('laravelusers::ui.never_expire'), 'checkbox', false, ['section' => 'expiry']);
        }

        return $fields;
    }

    public function delete(Request $request): array
    {
        $fields = [];
        if (GoodbyeEmail::allowed()) {
            $fields[] = $this->forms->field('send_goodbye', __('laravelusers::ui.goodbye_send'), 'checkbox', config('laravelusers.emails.goodbye_on_delete', false));
            foreach ($this->emailFields('goodbye', 'goodbye.') as $field) {
                $fields[] = $field + ['when' => ['key' => 'send_goodbye', 'equals' => true]];
            }
        }

        $form = $this->forms->form('delete-user', __('laravelusers::ui.delete'), null, 'DELETE', $fields, $request) + ['dialog' => true, 'danger' => true, 'confirm' => config('laravelusers.confirmDelete', true) ? __('laravelusers::ui.confirm_bulk') : null];
        $form['submit'] = __('laravelusers::ui.delete');

        return $form;
    }

    private function available(bool $deleted): bool
    {
        $gate = config('laravelusers.emails.gate');

        return auth()->check() && config('laravelusers.emails.enabled', false)
            && (!$gate || Gate::allows($gate))
            && (!$deleted || config('laravelusers.emails.deleted', true));
    }

    private function actionAllowed(string $action, bool $deleted): bool
    {
        return config('laravelusers.emails.'.$action, true) && UserAccess::email($action, $deleted)
            && ($action !== 'welcome' || config('laravelusers.welcome.enabled', false));
    }

    private function linkFields(string $kind, bool $deleted): array
    {
        $fields = [];
        if ($kind === 'reset' && config('laravelusers.emails.reset_duration', true)) {
            $broker = config('laravelusers.emails.password_broker') ?: config('auth.defaults.passwords');
            $fields = array_merge($fields, $this->expiryFields('reset', (int) config('auth.passwords.'.$broker.'.expire', 60), (bool) config('laravelusers.emails.reset_allow_never_expire', true)));
        }
        if ($deleted && config('laravelusers.account_links.enabled', false)) {
            foreach (['restore' => 'restore_users', 'force_delete' => 'force_delete'] as $link => $ability) {
                if (config('laravelusers.account_links.'.$link, true) && UserAccess::allows($ability)) {
                    $fields[] = $this->forms->field('include_'.$link, __('laravelusers::ui.account_include_'.$link), 'checkbox', false, ['section' => 'account-links']);
                }
            }
            $fields = array_merge($fields, $this->expiryFields('account', (int) config('laravelusers.account_links.expire', 60), (bool) config('laravelusers.account_links.allow_never_expire', true)));
        }

        return $fields;
    }
}
