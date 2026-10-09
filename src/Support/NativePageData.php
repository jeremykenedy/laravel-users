<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Composes independent serializers for the authorized screen sections.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
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

    private NativeFormData $forms;

    private NativeEmailForms $emails;

    private NativeAppearanceData $appearance;

    private NativeUserData $users;

    private NativeSettingsData $settings;

    private NativePageContext $context;

    private NativeNavigation $navigation;

    private NativeUserForms $userForms;

    private NativeAccountForms $accountForms;

    public function __construct(private Avatar $avatars, private UserActivity $activity, private ImpersonationSession $impersonation)
    {
        $this->forms = new NativeFormData();
        $this->emails = new NativeEmailForms($this->forms);
        $this->appearance = new NativeAppearanceData($this->forms);
        $this->users = new NativeUserData($avatars, $activity, $this->forms, $this->emails, new NativeUserColumns(), new NativeUserActions($this->emails));
        $this->settings = new NativeSettingsData($this->forms, new NativePackageForms($this->forms));
        $this->navigation = new NativeNavigation($this->emails);
        $this->context = new NativePageContext($this->navigation);
        $this->userForms = new NativeUserForms($this->forms, $this->users, $this->appearance, $avatars);
        $this->accountForms = new NativeAccountForms($this->forms, $this->users, $this->appearance, $avatars);
    }

    public function forView(string $view, array $data, Request $request): array
    {
        $page = $this->context->base($view, $data, $request);
        $screen = $page['screen'];
        $public = in_array($screen, ['account-link', 'confirm-email'], true);
        $page = match ($screen) {
            'users', 'deleted-users'   => $this->users->listing($page, $data, $request),
            'create-user', 'edit-user' => $this->userForms->page($page, $data, $request),
            'show-user'                => $this->users->profile($page, $data),
            'settings'                 => $this->settings->settings($page, $data, $request),
            'account'                  => $this->accountForms->page($page, $data, $request),
            default                    => $this->confirmation($page, $data, $request),
        };
        if (!$public) {
            $page['data']['appearance_defaults'] = $this->appearance->appearanceDefaults();
        }
        if ($page['features']['breadcrumbs']) {
            $page['data']['breadcrumbs'] = $this->navigation->breadcrumbs($page);
        }
        if (collect($page['forms'])->contains(fn ($form) => in_array('password', array_column($form['fields'], 'key'), true))) {
            $page['data']['password'] = ['settings' => PasswordRules::settings($screen === 'create-user'), 'strength_labels' => array_map(fn ($strength) => __('laravelusers::ui.password_'.$strength), ['weak', 'fair', 'good', 'strong']), 'feedback_delay' => max(0, (int) config('laravelusers.password.confirmation_debounce', 2000))];
        }
        $page = $this->currentUser($page, $request, $public);
        $page = $this->actionForms($page, $request);

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
            $page['forms']['confirmation'] = $this->forms->form('confirmation', $email ? $page['title'] : __('laravelusers::ui.account_'.$action), route($email ? 'users.account.email.accept' : 'users.account-link.confirm', ['token' => $data['token']]), 'POST', [], $request) + ['danger' => $action === 'force_delete'];
            $page['forms']['confirmation']['submit'] = $page['forms']['confirmation']['title'];
            $page['data']['form_ids'] = ['confirmation'];
        }
        if (!$valid) {
            $page['data']['notice'] = $this->completionNotice($email, $completed);
        }
        $page['data']['valid'] = $valid;
        $page['data']['completed'] = $completed;

        return $page;
    }

    private function actionForms(array $page, Request $request): array
    {
        $screen = $page['screen'];
        if (in_array($screen, ['users', 'deleted-users', 'show-user'], true)) {
            $page['forms'] += $this->emails->forms($screen === 'deleted-users', $request);
            $users = $page['data']['users'] ?? [$page['data']['user'] ?? []];
            $actions = collect($users)->flatMap(fn ($user) => array_column($user['actions'] ?? [], 'name'))->all();
            if (in_array('delete', $actions, true)) {
                $page['forms']['delete-user'] = $this->emails->delete($request);
            }
            if (in_array('restore', $actions, true)) {
                $page['forms']['restore-user'] = $this->forms->form('restore-user', __('laravelusers::ui.restore'), null, 'POST', [], $request);
                $page['forms']['restore-user']['submit'] = __('laravelusers::ui.restore');
            }
            if (in_array('force-delete', $actions, true)) {
                $page['forms']['force-delete-user'] = $this->forms->form('force-delete-user', __('laravelusers::ui.permanently_delete'), null, 'DELETE', [], $request) + ['danger' => true, 'confirm' => __('laravelusers::ui.confirm_bulk')];
                $page['forms']['force-delete-user']['submit'] = __('laravelusers::ui.permanently_delete');
            }
            if (in_array('impersonate', $actions, true)) {
                $page['forms']['impersonate-user'] = $this->forms->form('impersonate-user', __('laravelusers::ui.impersonation_target'), null, 'POST', [], $request);
                $page['forms']['impersonate-user']['submit'] = __('laravelusers::ui.confirm');
            }
        }

        return $page;
    }

    private function currentUser(array $page, Request $request, bool $public): array
    {
        if (!$public && $request->user() instanceof Model) {
            $avatar = $this->avatars->forUser($request->user());
            if ($avatar) {
                $avatar['size'] = 28;
            }
            $page['data']['current_user'] = $this->users->identity($request->user()) + ['avatar' => $avatar, 'activity' => config('laravelusers.activity.login', false) ? ($this->activity->listing([$request->user()], true)[$request->user()->getKey()] ?? []) : null];
            $state = $request->hasSession() ? $this->impersonation->read($request) : null;
            if ($state) {
                $page['data']['banner'] = ['message' => $state['actor_name'].': '.$request->user()->name, 'action' => route('users.impersonation.stop'), 'label' => __('laravelusers::ui.impersonation_stop')];
            }
        }

        return $page;
    }

    private function completionNotice(bool $email, mixed $completed): string
    {
        $key = $email ? ($completed === 'complete' ? 'account_email_complete' : ($completed === 'pending' ? 'account_email_waiting' : 'account_email_invalid')) : ($completed ? 'account_'.$completed.'_done' : 'account_link_invalid_help');

        return __('laravelusers::ui.'.$key);
    }
}
