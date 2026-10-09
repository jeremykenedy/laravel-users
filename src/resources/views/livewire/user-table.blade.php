<div class="lu-native-users" data-lu-native-table>
    <div class="lu-pad lu-actions lu-table-controls">
        @if($features['filtering'] ?? false)
            <label class="lu-field" for="lu-native-table-filter"><span>{{ $labels['filter'] ?? __('laravelusers::ui.filters') }}</span><input class="lu-input" type="search" id="lu-native-table-filter" wire:model.live.debounce.250ms="filter" maxlength="255"></label>
        @endif
        @if($features['view_toggle'] ?? false)
            <div class="lu-actions" role="group" aria-label="{{ $labels['view'] ?? __('laravelusers::ui.list_view') }}">
                @foreach(['table', 'cards'] as $view)<button type="button" class="lu-button lu-secondary" wire:click="setMode('{{ $view }}')" aria-pressed="{{ $mode === $view ? 'true' : 'false' }}">{{ $labels[$view] ?? __('laravelusers::ui.'.($view === 'table' ? 'table_view' : 'card_view')) }}</button>@endforeach
            </div>
        @endif
        @if($features['columns'] ?? false)
            <details class="lu-column-controls"><summary>{{ $labels['columns'] ?? __('laravelusers::ui.columns') }}</summary><div class="lu-pad">
                @foreach($columns as $column)<label class="lu-check" wire:key="column-{{ $column['key'] }}"><input type="checkbox" wire:click="toggleColumn('{{ $column['key'] }}')" @if(!in_array($column['key'], $hiddenColumns, true)) checked @endif><span>{{ $column['label'] }}</span></label>@endforeach
            </div></details>
        @endif
    </div>
    @if($features['bulk'] ?? false)
        <div class="lu-pad lu-actions"><span role="status">{{ str_replace(':count', (string) count($selected), $labels['selected'] ?? __('laravelusers::ui.selected', ['count' => ':count'])) }}</span>
            @foreach($features['bulk_actions'] ?? [] as $action)<button type="button" class="lu-button {{ $action['class'] ?? 'lu-secondary' }}" wire:click="requestBulkAction('{{ $action['name'] }}')" @if(!$selected) disabled @endif><x-laravelusers::livewire.icon :action="$action['name']"/><span>{{ $action['label'] }}</span></button>@endforeach
        </div>
    @endif
    @if($mode === 'cards' && ($features['view_toggle'] ?? false))
        <div class="lu-native-cards lu-pad">
            @forelse($displayUsers as $user)
                <article class="lu-panel lu-pad lu-native-user-card {{ \jeremykenedy\laravelusers\Support\Frontend::classes('panel') }}" wire:key="card-{{ $user['id'] }}">
                    <header class="lu-heading">
                        @if($user['avatar'] ?? null)<x-laravelusers::livewire.avatar :avatar="$user['avatar']" />@endif
                        <h2>@if($user['urls']['show'] ?? null)<a href="{{ $user['urls']['show'] }}" wire:navigate>{{ $user['name'] }}</a>@else{{ $user['name'] }}@endif</h2>
                        @if($features['bulk'] ?? false)<label class="lu-check"><input type="checkbox" wire:model.live="selected" value="{{ $user['id'] }}" @if(!($user['selectable'] ?? true)) disabled @endif><span class="lu-sr-only">{{ __('laravelusers::ui.select_user', ['name' => $user['name']]) }}</span></label>@endif
                    </header>
                    <dl class="lu-details">@foreach($visibleColumns as $column)<div><dt>{{ $column['label'] }}</dt><dd><x-laravelusers::livewire.cell :user="$user" :column="$column" :text="$this->cellText($user, $column)" /></dd></div>@endforeach</dl>
                    <x-laravelusers::livewire.user-actions :user="$user" />
                </article>
            @empty<p role="status">{{ $labels['empty'] ?? __('laravelusers::laravelusers.search.no-results') }}</p>@endforelse
        </div>
    @else
        <div class="lu-scroll {{ \jeremykenedy\laravelusers\Support\Frontend::classes('scroll') }}"><table class="lu-native-table {{ \jeremykenedy\laravelusers\Support\Frontend::classes('table') }}">
            <caption>{{ $labels['directory'] ?? __('laravelusers::ui.directory') }}</caption>
            <thead><tr>
                @if($features['bulk'] ?? false)<th scope="col"><button type="button" class="lu-button lu-secondary" wire:click="selectAll">{{ $labels['select_all'] ?? __('laravelusers::ui.select_all') }}</button></th>@endif
                @foreach($visibleColumns as $column)
                    <th scope="col" wire:key="heading-{{ $column['key'] }}" @if($sort === $column['key']) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                        @if(($features['sorting'] ?? true) && ($column['sortable'] ?? true))<button type="button" class="lu-native-sort" wire:click="sortBy('{{ $column['key'] }}')">{{ $column['label'] }}</button>@else{{ $column['label'] }}@endif
                    </th>
                @endforeach
                <th scope="col">{{ $labels['actions'] ?? __('laravelusers::laravelusers.users-table.actions') }}</th>
            </tr></thead>
            <tbody>
                @forelse($displayUsers as $user)
                    <tr wire:key="user-{{ $user['id'] }}">
                        @if($features['bulk'] ?? false)<td><label class="lu-check"><input type="checkbox" wire:model.live="selected" value="{{ $user['id'] }}" @if(!($user['selectable'] ?? true)) disabled @endif><span class="lu-sr-only">{{ __('laravelusers::ui.select_user', ['name' => $user['name']]) }}</span></label></td>@endif
                        @foreach($visibleColumns as $column)<td><x-laravelusers::livewire.cell :user="$user" :column="$column" :text="$this->cellText($user, $column)" /></td>@endforeach
                        <td><x-laravelusers::livewire.user-actions :user="$user" /></td>
                    </tr>
                @empty<tr><td colspan="{{ count($visibleColumns) + 1 + (int) ($features['bulk'] ?? false) }}" role="status">{{ $labels['empty'] ?? __('laravelusers::laravelusers.search.no-results') }}</td></tr>@endforelse
            </tbody>
        </table></div>
    @endif
</div>
