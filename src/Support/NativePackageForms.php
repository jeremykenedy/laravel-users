<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class NativePackageForms
{
    public function __construct(private NativeFormData $forms)
    {
    }

    public function settings(array $page, array $data, Request $request): array
    {
        if (!($data['packageManagementAllowed'] ?? false)) {
            return $page;
        }
        $page['data']['packages'] = ['installed' => $data['managedPackages'] ?? [], 'ready' => (bool) ($data['packageQueueReady'] ?? false), 'verify' => route('users.settings.packages.verify'), 'status_url' => route('users.settings.packages.status', ['id' => '__JOB_ID__'])];
        $page['data']['packages']['requirements'] = is_array($data['packageRequirements'] ?? null) ? Arr::only($data['packageRequirements'], ['status', 'queue_ready', 'message']) : null;
        $page['data']['packages']['choices'] = [];
        $docs = 'https://laravel.com/docs/'.explode('.', Application::VERSION)[0].'.x';
        $page['data']['packages']['help'] = [['label' => __('laravelusers::ui.packages_queue_setup'), 'url' => $docs.'/queues#introduction'], ['label' => __('laravelusers::ui.packages_worker_setup'), 'url' => $docs.'/queues#running-the-queue-worker'], ['label' => __('laravelusers::ui.packages_cache_setup'), 'url' => $docs.'/cache#atomic-locks']];
        $page['forms']['package-verify'] = $this->forms->form('package-verify', __('laravelusers::ui.package_requirements'), route('users.settings.packages.verify'), 'POST', [$this->forms->field('package', '', 'hidden', 'requirements'), $this->forms->field('operation', '', 'hidden', 'verify')], $request) + ['async' => true, 'verify' => true];
        $page['forms']['package-verify']['submit'] = __('laravelusers::ui.package_requirements_verify');
        $page['data']['package_operation'] = is_array($data['packageOperation'] ?? null) ? Arr::only($data['packageOperation'], ['id', 'status_url', 'status', 'stage', 'package', 'operation', 'message', 'queued_at', 'started_at', 'updated_at']) : null;
        foreach (['requirements' => 'Package requirements', 'toast' => 'Laravel Toast', 'laravel-roles' => 'Laravel Roles', 'spatie' => 'Spatie Permissions'] as $package => $label) {
            $page = $this->operation($page, $package, $label, $data, $request);
        }
        if ($data['accessAvailable'] ?? false) {
            $page['forms']['impersonation-settings'] = $this->forms->form('impersonation-settings', __('laravelusers::ui.impersonation'), route('users.settings.impersonation'), 'POST', [$this->forms->field('enabled', __('laravelusers::ui.impersonation'), 'checkbox', (bool) ($data['impersonationEnabled'] ?? false))], $request) + ['disabled' => !($data['settingsAvailable'] ?? false)];
            $page['data']['form_ids'][] = 'impersonation-settings';
        }

        return $page;
    }

    private function operation(array $page, string $package, string $label, array $data, Request $request): array
    {
        $state = $this->state($package, $data);
        $fields = $this->operationFields($package, $state);
        $id = 'package-'.$package;
        $page['forms'][$id] = $this->forms->form($id, $label, route('users.settings.packages'), 'POST', $fields, $request) + ['dialog' => true, 'async' => true, 'disabled' => $state['disabled'], 'blocked' => $state['blocked'], 'requires_queue' => $state['requiresQueue'], 'danger' => $state['operation'] === 'remove', 'help' => $state['operation'] === 'remove' ? __('laravelusers::ui.packages_remove_roles_warning') : __('laravelusers::ui.packages_acknowledgement')];
        $page['data']['settings_actions'][] = ['name' => $id, 'label' => $label.' ('.$state['operation'].')', 'form' => $id, 'disabled' => $state['disabled'] || ($package === 'requirements' && ($data['packageQueueReady'] ?? false))];
        if ($package !== 'requirements') {
            $page['data']['packages']['choices'][] = $this->choice($package, $label, $state);
        }
        if ($state['installed'] && !$state['setupCompleted'] && $package !== 'requirements') {
            $page = $this->configure($page, $package, $label, $state, $request);
        }

        return $page;
    }

    private function state(string $package, array $data): array
    {
        $installed = (bool) ($data['managedPackages'][$package] ?? false);
        $operation = $package === 'requirements' ? 'setup' : ($installed ? 'remove' : 'install');
        $word = $operation === 'remove' ? 'remove' : 'continue';
        $conflict = $this->roleConflict($package, $installed, $data);
        $unsupported = $this->toastUnsupported($package, $installed);
        $requiresQueue = $package !== 'requirements';
        $blocked = $conflict || $unsupported;
        $disabled = $blocked || ($requiresQueue && !($data['packageQueueReady'] ?? false));
        $setupCompleted = $package === 'toast' && $installed && (bool) ($data['managedPackageSetup']['toast'] ?? false);

        return compact('installed', 'operation', 'word', 'conflict', 'unsupported', 'requiresQueue', 'blocked', 'disabled', 'setupCompleted');
    }

    private function roleConflict(string $package, bool $installed, array $data): bool
    {
        return !$installed && in_array($package, ['laravel-roles', 'spatie'], true)
            && (($data['managedPackages']['laravel-roles'] ?? false) || ($data['managedPackages']['spatie'] ?? false));
    }

    private function toastUnsupported(string $package, bool $installed): bool
    {
        return !$installed && $package === 'toast'
            && (PHP_VERSION_ID < 80200 || version_compare(Application::VERSION, '10.0.0', '<'));
    }

    private function operationFields(string $package, array $state): array
    {
        $fields = [$this->forms->field('package', '', 'hidden', $package), $this->forms->field('operation', '', 'hidden', $state['operation']), $this->forms->field('setup', __('laravelusers::ui.package_publish_missing'), 'checkbox', true), $this->forms->field('migrate', __('laravelusers::ui.package_run_migrations'), 'checkbox', false), $this->forms->field('acknowledgement', __('laravelusers::ui.packages_acknowledgement'), 'checkbox', false, ['required' => true]), $this->forms->field('confirmation', __('laravelusers::ui.package_confirmation').' '.$state['word'], 'text', '', ['required_text' => $state['word']])];
        if ($package === 'toast') {
            $fields = array_values(array_filter($fields, fn ($field) => !in_array($field['key'], ['setup', 'migrate'], true)));
        }

        return $fields;
    }

    private function choice(string $package, string $label, array $state): array
    {
        $id = 'package-'.$package;

        return ['name' => $id, 'package' => $package, 'label' => $label, 'installed' => $state['installed'], 'operation' => $state['operation'], 'blocked' => $state['blocked'], 'disabled' => $state['disabled'], 'reason' => $state['conflict'] ? __('laravelusers::ui.packages_roles_conflict') : ($state['unsupported'] ? __('laravelusers::ui.packages_toast_unsupported') : null), 'hint' => $package === 'toast' ? __('laravelusers::ui.packages_toast_hint') : null, 'setup_hint' => $state['installed'] && !$state['setupCompleted'] ? __('laravelusers::ui.'.($package === 'toast' ? 'package_toast_managed_setup' : 'package_roles_setup')) : null, 'setup_completed' => $state['setupCompleted'], 'configure_name' => $state['installed'] && !$state['setupCompleted'] ? $id.'-configure' : null];
    }

    private function configure(array $page, string $package, string $label, array $state, Request $request): array
    {
        $id = 'package-'.$package;
        $configureId = $id.'-configure';
        $configureFields = [$this->forms->field('package', '', 'hidden', $package), $this->forms->field('operation', '', 'hidden', 'configure')];
        if ($package !== 'toast') {
            $configureFields[] = $this->forms->field('migrate', __('laravelusers::ui.package_run_migrations'), 'checkbox', false);
        }
        $configureFields[] = $this->forms->field('acknowledgement', __('laravelusers::ui.packages_acknowledgement'), 'checkbox', false, ['required' => true]);
        $configureFields[] = $this->forms->field('confirmation', __('laravelusers::ui.package_confirmation').' continue', 'text', '', ['required_text' => 'continue']);
        $page['forms'][$configureId] = $this->forms->form($configureId, __('laravelusers::ui.package_configure').' '.$label, route('users.settings.packages'), 'POST', $configureFields, $request) + ['dialog' => true, 'async' => true, 'disabled' => $state['disabled'], 'blocked' => false, 'requires_queue' => true];
        $page['data']['settings_actions'][] = ['name' => $configureId, 'label' => __('laravelusers::ui.package_configure'), 'form' => $configureId, 'disabled' => $state['disabled']];

        return $page;
    }
}
