<div data-lu-native-screen="{{ $page['screen'] }}">
    <x-laravelusers::livewire.navigation :page="$page" />
    <div class="lu-native-notifications" data-lu-notification-style="{{ $page['features']['notification_driver'] }}">@foreach($page['flash'] as $index => $message)@if($page['features']['notifications'] || $message['type'] === 'error')<div class="lu-flash" role="{{ $message['type'] === 'error' ? 'alert' : 'status' }}"><span>{{ $message['message'] }}</span>@if($page['features']['notification_dismissible'])<button type="button" aria-label="{{ $page['labels']['close'] }}" wire:click="dismissFlash({{ $index }})">{{ $page['labels']['close'] }}</button>@endif</div>@endif @endforeach</div>
    <div wire:ignore>@include('laravelusers::partials.toasts', ['userToasts' => $page['data']['toasts']])</div>
    <x-laravelusers::livewire.package-status :page="$page" />
    <script type="application/json" data-lu-native-client>{!! json_encode($page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    @if($page['data']['banner'] ?? null)
        <div class="lu-alert" role="status">{{ $page['data']['banner']['message'] }}<form method="POST" action="{{ $page['data']['banner']['action'] }}">@csrf<button type="submit" class="lu-button lu-secondary">{{ $page['data']['banner']['label'] }}</button></form></div>
    @endif
    <section class="lu-panel {{ $page['classes']['panel'] }}">
        <header class="lu-heading lu-card-heading"><h1>{{ $page['title'] }}</h1><div class="lu-actions">@foreach($page['data']['navigation'] as $link)<a class="lu-button lu-secondary" href="{{ $link['url'] }}" wire:navigate>{{ $link['label'] }}</a>@endforeach</div></header>
        @if($page['data']['notice'] ?? null)<p class="lu-pad" role="status">{{ $page['data']['notice'] }}</p>@endif
        @if(in_array($page['screen'], ['users', 'deleted-users'], true))
            @if($page['features']['search'])<form class="lu-search" wire:submit="searchUsers"><label for="lu-native-search" class="lu-sr-only">{{ $page['labels']['search'] }}</label><input class="lu-input" id="lu-native-search" type="search" @if($page['features']['search_debounce'] !== null) wire:model.live.debounce.{{ $page['features']['search_debounce'] }}ms="search" @else wire:model="search" @endif maxlength="255"><button type="submit" class="lu-button">{{ $page['labels']['search'] }}</button>@if($search !== '')<button type="button" class="lu-button lu-secondary" wire:click="clearSearch">{{ $page['labels']['clear'] }}</button>@endif</form>@endif
            @livewire('laravelusers.user-table', ['users' => $page['data']['users'], 'columns' => $page['data']['columns'], 'features' => $page['features'], 'labels' => $page['labels']], key('table-'.$page['screen']))
            <div class="lu-pagination">
                @if($page['features']['show_count'])<span>{{ __('laravelusers::ui.total_users', ['count' => $page['data']['pagination']['total']]) }}</span>@endif
                @if($page['data']['pagination']['enabled'])<nav class="lu-actions" aria-label="{{ __('laravelusers::ui.pagination') }}">@if($page['data']['pagination']['previous'])<a class="lu-button lu-secondary" href="{{ $page['data']['pagination']['previous'] }}" wire:navigate>{{ $page['labels']['previous'] }}</a>@endif<span>{{ __('laravelusers::ui.page', ['page' => $page['data']['pagination']['current'], 'total' => $page['data']['pagination']['last']]) }}</span>@if($page['data']['pagination']['next'])<a class="lu-button lu-secondary" href="{{ $page['data']['pagination']['next'] }}" wire:navigate>{{ $page['labels']['next'] }}</a>@endif</nav>@endif
            </div>
        @elseif(in_array($page['screen'], ['show-user', 'account', 'edit-user'], true))
            @php($user = $page['data']['user'])
            <div class="lu-profile-body">
                <header class="lu-profile-identity lu-native-profile" data-lu-native-user-profile>@if($user['avatar'] ?? null)<x-laravelusers::livewire.avatar :avatar="$user['avatar']" />@endif<div><h2>{{ $user['full_name'] ?? $user['name'] }}</h2><p>{{ $user['email'] }}</p></div></header>
                @if($user['pending_email'] ?? null)<p role="status">{{ __('laravelusers::ui.account_email_pending_to', ['email' => $user['pending_email']]) }}</p>@endif
                @if($page['screen'] === 'show-user')
                    <dl class="lu-profile-details">@foreach($page['data']['columns'] as $column)@if(($column['type'] ?? '') !== 'avatar')<div class="lu-detail"><dt>{{ $column['label'] }}</dt><dd><x-laravelusers::livewire.cell :user="$user" :column="$column" :text="is_array(\Illuminate\Support\Arr::get($user, $column['key'])) ? implode(', ', array_column(\Illuminate\Support\Arr::get($user, $column['key']), 'name')) : (string) \Illuminate\Support\Arr::get($user, $column['key'])" /></dd></div>@endif @endforeach</dl>
                    @if($user['permissions'] ?? null)<p>{{ $page['labels']['direct_permissions'] }}: {{ implode(', ', array_column($user['permissions'], 'label')) }}</p>@endif
                    @if($user['role_level'] ?? null)<p>{{ __('laravelusers::ui.role_level', ['level' => $user['role_level']]) }}</p>@endif
                    <div class="lu-actions">@foreach($user['links'] as $link)<a class="lu-button lu-secondary" href="{{ $link['url'] }}" wire:navigate>{{ $link['label'] }}</a>@endforeach @foreach($user['actions'] as $action)<button type="button" class="lu-button {{ $action['class'] ?? 'lu-secondary' }}" wire:click="openUserAction('{{ $action['name'] }}', '{{ $user['id'] }}')" @if($action['disabled'] ?? false) disabled @endif>{{ $action['label'] }}</button>@endforeach</div>
                @endif
            </div>
        @endif
        @foreach($page['data']['form_ids'] as $id)<x-laravelusers::livewire.form :form="$page['forms'][$id]" :values="$values[$id]" :active-tab="$tabs[$id]" :ready="$this->formReady($id)" :page="$page" />@endforeach
        <x-laravelusers::livewire.package-settings :page="$page" />
        @if($page['data']['settings_actions'] ?? null)<div class="lu-pad lu-actions">@foreach(array_filter($page['data']['settings_actions'], fn ($action) => !str_starts_with($action['name'], 'package-')) as $action)<button type="button" class="lu-button {{ $action['class'] ?? 'lu-secondary' }}" wire:click="openSettingsAction('{{ $action['name'] }}')" @if($action['disabled'] ?? false) disabled @endif>{{ $action['label'] }}</button>@endforeach</div>@endif
    </section>
    @if($dialogForm)
        <x-laravelusers::livewire.dialog id="lu-native-action" :title="$dialogForm['title']">
            <x-laravelusers::livewire.form :form="$dialogForm" :values="$values[$activeForm]" :active-tab="$tabs[$activeForm]" :dialog="true" :ready="$dialogReady" :page="$page" />
        </x-laravelusers::livewire.dialog>
    @endif
</div>
