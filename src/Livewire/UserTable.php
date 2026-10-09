<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Component;

class UserTable extends Component
{
    #[Locked]
    public array $users = [];

    #[Locked]
    public array $columns = [];

    #[Locked]
    public array $features = [];

    #[Locked]
    public array $labels = [];

    public string $filter = '';

    public string $sort = 'id';

    public string $direction = 'desc';

    public string $mode = 'table';

    public array $selected = [];

    public array $hiddenColumns = [];

    public function mount(array $users, array $columns, array $features = [], array $labels = []): void
    {
        $this->users = $users;
        $this->columns = $columns;
        $this->features = $features;
        $this->labels = $labels;
    }

    public function sortBy(string $column): void
    {
        if (!($this->features['sorting'] ?? true) || !in_array($column, $this->sortableColumns(), true)) {
            return;
        }
        $this->direction = $this->sort === $column && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $column;
    }

    public function toggleColumn(string $column): void
    {
        if (!($this->features['columns'] ?? false) || !in_array($column, array_column($this->columns, 'key'), true)) {
            return;
        }
        if (in_array($column, $this->hiddenColumns, true)) {
            $this->hiddenColumns = array_values(array_diff($this->hiddenColumns, [$column]));
        } elseif (count($this->visibleColumns()) > 1) {
            $this->hiddenColumns[] = $column;
        }
    }

    public function setMode(string $mode): void
    {
        if (($this->features['view_toggle'] ?? false) && in_array($mode, ['table', 'cards'], true)) {
            $this->mode = $mode;
        }
    }

    public function selectAll(): void
    {
        if (!($this->features['bulk'] ?? false)) {
            return;
        }
        $ids = array_map('strval', array_column(array_filter($this->displayUsers(), fn ($user) => $user['selectable'] ?? true), 'id'));
        $this->selected = array_diff($ids, $this->selected) === [] ? array_values(array_diff($this->selected, $ids)) : array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function requestAction(string $action, string $id): void
    {
        $user = collect($this->users)->first(fn ($user) => (string) $user['id'] === $id);
        if ($user && in_array($action, array_column($user['actions'] ?? [], 'name'), true)) {
            $this->dispatch('laravelusers-user-action', action: $action, id: $id);
        }
    }

    public function requestBulkAction(string $action): void
    {
        $actions = array_column($this->features['bulk_actions'] ?? [], 'name');
        $ids = array_values(array_intersect(array_map('strval', $this->selected), array_map('strval', array_column($this->users, 'id'))));
        if ($ids && ($this->features['bulk'] ?? false) && in_array($action, $actions, true)) {
            $this->dispatch('laravelusers-bulk-action', action: $action, ids: $ids);
        }
    }

    public function render(): View
    {
        return view('laravelusers::livewire.user-table', [
            'displayUsers'   => $this->displayUsers(),
            'visibleColumns' => $this->visibleColumns(),
        ]);
    }

    public function cellText(array $user, array $column): string
    {
        $value = Arr::get($user, $column['key']);
        if (is_array($value)) {
            return implode(', ', array_map(fn ($item) => is_array($item) ? ($item['name'] ?? $item['label'] ?? '') : (string) $item, $value));
        }

        return is_scalar($value) ? (string) $value : '';
    }

    private function visibleColumns(): array
    {
        return array_values(array_filter($this->columns, fn ($column) => !in_array($column['key'], $this->hiddenColumns, true)));
    }

    private function sortableColumns(): array
    {
        return array_column(array_filter($this->columns, fn ($column) => $column['sortable'] ?? true), 'key');
    }

    private function displayUsers(): array
    {
        $users = $this->users;
        if (($this->features['filtering'] ?? false) && trim($this->filter) !== '') {
            $filter = mb_strtolower(trim($this->filter));
            $users = array_values(array_filter($users, function ($user) use ($filter) {
                foreach ($this->columns as $column) {
                    if (str_contains(mb_strtolower($this->cellText($user, $column)), $filter)) {
                        return true;
                    }
                }

                return false;
            }));
        }
        if (($this->features['sorting'] ?? true) && in_array($this->sort, $this->sortableColumns(), true)) {
            $column = collect($this->columns)->firstWhere('key', $this->sort);
            usort($users, function ($left, $right) use ($column) {
                $comparison = strnatcasecmp($this->cellText($left, $column), $this->cellText($right, $column));

                return $this->direction === 'asc' ? $comparison : -$comparison;
            });
        }

        return $users;
    }
}
