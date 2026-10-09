<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class UsersScreen extends Component
{
    #[Locked]
    public array $page = [];

    #[Locked]
    public ?string $activeForm = null;

    #[Locked]
    public ?string $activeAction = null;

    public array $values = [];

    public array $tabs = [];

    public string $search = '';

    public function mount(array $nativePage): void
    {
        $this->page = $nativePage;
        foreach ($nativePage['forms'] as $id => $form) {
            $this->values[$id] = $form['values'];
            $sections = array_column($form['fields'], 'section');
            $this->tabs[$id] = $sections[0] ?? 'profile';
            foreach ($form['errors'] as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError('values.'.$id.'.'.$field, $message);
                }
            }
        }
    }

    public function setTab(string $form, string $section): void
    {
        if (isset($this->page['forms'][$form]) && in_array($section, array_column($this->page['forms'][$form]['fields'], 'section'), true)) {
            $this->tabs[$form] = $section;
        }
    }

    public function searchUsers(): void
    {
        if ($this->page['features']['search'] ?? false) {
            $this->redirect(route('users', trim($this->search) === '' ? [] : ['user_search_box' => mb_substr(trim($this->search), 0, 255)]), navigate: true);
        }
    }

    public function prepareSubmit(string $form): void
    {
        if (!isset($this->page['forms'][$form]) || ($this->page['forms'][$form]['disabled'] ?? false)) {
            return;
        }
        if ($this->page['forms'][$form]['confirm'] ?? null) {
            $this->activeForm = $form;
            $this->activeAction = null;
        } elseif ($this->ready($form)) {
            $this->dispatch('laravelusers-native-submit', form: $form);
        }
    }

    public function confirmSubmit(): void
    {
        if ($this->activeForm && $this->ready($this->activeForm)) {
            $this->dispatch('laravelusers-native-submit', form: $this->activeForm, dialog: true);
        }
    }

    public function closeDialog(): void
    {
        if ($this->activeForm) {
            foreach ($this->page['forms'][$this->activeForm]['fields'] as $field) {
                if ($field['type'] === 'password') {
                    Arr::set($this->values[$this->activeForm], $field['key'], '');
                }
            }
        }
        $this->activeForm = null;
        $this->activeAction = null;
    }

    public function openSettingsAction(string $name): void
    {
        $action = collect($this->page['data']['settings_actions'] ?? [])->firstWhere('name', $name);
        if ($action && !($action['disabled'] ?? false)) {
            $this->activate($action);
        }
    }

    #[On('laravelusers-user-action')]
    public function openUserAction(string $action, string $id): void
    {
        $users = $this->page['data']['users'] ?? [$this->page['data']['user'] ?? []];
        $user = collect($users)->first(fn ($user) => (string) ($user['id'] ?? '') === $id);
        $choice = collect($user['actions'] ?? [])->firstWhere('name', $action);
        if ($choice && !($choice['disabled'] ?? false)) {
            $this->activate($choice);
        }
    }

    #[On('laravelusers-bulk-action')]
    public function openBulkAction(string $action, array $ids): void
    {
        $choice = collect($this->page['features']['bulk_actions'] ?? [])->firstWhere('name', $action);
        $ids = array_values(array_intersect(array_map('strval', $ids), array_map('strval', array_column($this->page['data']['users'] ?? [], 'id'))));
        if ($choice && $ids) {
            $this->activate($choice + ['values' => ['ids' => $ids]]);
        }
    }

    public function toggleInheritance(string $form, string $key): void
    {
        $field = collect($this->page['forms'][$form]['fields'] ?? [])->firstWhere('key', $key);
        if ($field && ($field['nullable'] ?? false)) {
            Arr::set($this->values[$form], $key, Arr::get($this->values[$form], $key) === null ? $field['fallback'] : null);
        }
    }

    public function render(): View
    {
        $form = $this->activeForm ? $this->page['forms'][$this->activeForm] : null;
        if ($form && $this->activeAction) {
            $form['action'] = $this->activeAction;
        }

        return view('laravelusers::livewire.users-screen', ['dialogForm' => $form, 'dialogReady' => $this->activeForm ? $this->ready($this->activeForm) : false]);
    }

    private function activate(array $action): void
    {
        $id = $action['form'];
        if (!isset($this->page['forms'][$id]) || ($this->page['forms'][$id]['disabled'] ?? false)) {
            return;
        }
        $this->activeForm = $id;
        $this->activeAction = $action['url'] ?? null;
        foreach ($action['values'] ?? [] as $key => $value) {
            Arr::set($this->values[$id], $key, $value);
        }
    }

    private function ready(string $id): bool
    {
        foreach ($this->page['forms'][$id]['fields'] as $field) {
            if (isset($field['when']) && Arr::get($this->values[$id], $field['when']['key']) != $field['when']['equals']) {
                continue;
            }
            $value = Arr::get($this->values[$id], $field['key']);
            if ((isset($field['required_text']) && $value !== $field['required_text']) || ($field['type'] === 'checkbox' && ($field['required'] ?? false) && !$value)) {
                return false;
            }
        }

        return true;
    }
}
