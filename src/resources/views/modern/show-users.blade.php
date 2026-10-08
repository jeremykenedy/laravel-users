@extends('laravelusers::modern.page')
@section('template_title', __('laravelusers::laravelusers.showing-all-users'))
@section('users_content')
    @php
        $tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind';
        $onlineColumn = config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', true);
        $loginColumn = config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', true);
        $columns = 4 + (int) config('laravelusers.bulkActions', false) + (int) config('laravelusers.avatar.enabled', false) + (int) config('laravelusers.showCreatedColumn', true) + (int) config('laravelusers.showUpdatedColumn', true) + (int) config('laravelusers.rolesEnabled') + (int) $onlineColumn + (int) $loginColumn;
    @endphp
    <section class="lu-panel {{ $tailwind ? 'lu:rounded-xl lu:border lu:shadow-sm' : 'card' }}" aria-label="{{ __('laravelusers::app.nav.users') }}">
        <header class="lu-heading lu-card-heading">
            <h1>{{ __('laravelusers::laravelusers.showing-all-users') }}</h1>
            <div class="lu-actions">
            @if(config('laravelusers.softDeletedEnabled', false))<a class="lu-button lu-secondary" href="{{ route('users.deleted') }}">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::laravelusers.show-deleted-users') }}</a>@endif
            <a class="lu-button lu-success" href="{{ route('users.create') }}">@include('laravelusers::partials.icon', ['name' => 'add-user']) {{ __('laravelusers::laravelusers.create-new-user') }}</a>
            </div>
        </header>
        @if(config('laravelusers.enableSearchUsers'))
            <form class="lu-search" id="lu-search" action="{{ route('search-users') }}" method="POST">
                @csrf
                @if(config('laravelusers.avatar.enabled', false))<input type="hidden" name="include_avatar" value="1">@endif
                @if($onlineColumn || $loginColumn)<input type="hidden" name="include_activity" value="1">@endif
                <div class="lu-search-field"><label for="user_search_box" class="lu-sr-only">{{ __('laravelusers::forms.search-users-ph') }}</label><div class="lu-input-group"><input class="lu-input {{ $tailwind ? 'lu:w-full lu:rounded-lg' : 'form-control' }}" type="search" id="user_search_box" name="user_search_box" placeholder="{{ __('laravelusers::forms.search-users-ph') }}" maxlength="255" required></div></div>
                <button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'search']) {{ __('laravelusers::ui.search') }}</button>
                <button class="lu-button lu-secondary" type="reset">@include('laravelusers::partials.icon', ['name' => 'close']) {{ __('laravelusers::ui.clear') }}</button>
            </form>
            <p id="lu-search-status" class="lu-pad lu-muted" role="status" hidden></p>
        @endif
        @include('laravelusers::partials.bulk-actions', ['modern' => true, 'deleted' => false])
        <div class="lu-scroll {{ $tailwind ? 'lu:overflow-x-auto' : 'table-responsive' }}">
            <table data-lu-view="users" class="{{ $tailwind ? 'lu:w-full lu:text-left' : 'table' }}" data-lu-table>
                <caption>{{ __('laravelusers::ui.directory') }}</caption>
                <thead><tr>
                    @if(config('laravelusers.avatar.enabled', false))<th scope="col" data-lu-no-sort>{{ __('laravelusers::ui.avatar') }}</th>@endif
                    @if(config('laravelusers.bulkActions', false))@include('laravelusers::partials.select-all')@endif
                    @foreach(['id', 'name', 'email'] as $column)<th scope="col">{{ __('laravelusers::laravelusers.users-table.'.$column) }}</th>@endforeach
                    @if(config('laravelusers.rolesEnabled'))<th scope="col">{{ __('laravelusers::laravelusers.users-table.role') }}</th>@endif
                    @if($onlineColumn)<th scope="col">{{ __('laravelusers::ui.presence') }}</th>@endif
                    @foreach(['created' => 'showCreatedColumn', 'updated' => 'showUpdatedColumn'] as $column => $setting)
                        @if(config('laravelusers.'.$setting, true))<th scope="col">{{ __('laravelusers::laravelusers.users-table.'.$column) }}</th>@endif
                    @endforeach
                    @if($loginColumn)<th scope="col">{{ __('laravelusers::ui.last_login_at') }}</th>@endif
                    <th scope="col" data-lu-no-sort>{{ __('laravelusers::laravelusers.users-table.actions') }}</th>
                </tr></thead>
                <tbody id="lu-users">
                    @forelse($users as $user)
                        <tr>
                            @if(config('laravelusers.avatar.enabled', false))<td>@include('laravelusers::partials.avatar', ['avatar' => $userAvatars[$user->getKey()]])</td>@endif
                            @if(config('laravelusers.bulkActions', false))<td>@include('laravelusers::partials.select-user')</td>@endif
                            <td>{{ $user->id }}</td><td><a href="{{ route('users.show', $user->id) }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.view_user') }}" @endif>{{ $user->name }}</a></td>
                            <td>@if(config('laravelusers.emailLinks', true))<a href="mailto:{{ $user->email }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.email_user') }}" @endif>{{ $user->email }}</a>@else{{ $user->email }}@endif</td>
                            @if(config('laravelusers.rolesEnabled'))<td>{{ $user->roles->pluck('name')->implode(', ') }}</td>@endif
                            @if($onlineColumn)<td data-lu-value="{{ ($userActivity[$user->getKey()]['online'] ?? false) ? 'online' : 'offline' }}">@if(($userActivity[$user->getKey()]['online'] ?? false) === true)<span class="lu-badge lu-online">{{ __('laravelusers::ui.online') }}</span>@endif</td>@endif
                            @foreach(['created_at' => 'showCreatedColumn', 'updated_at' => 'showUpdatedColumn'] as $column => $setting)
                                @if(config('laravelusers.'.$setting, true))<td>@include('laravelusers::partials.date', ['value' => $user->$column])</td>@endif
                            @endforeach
                            @if($loginColumn)<td>@include('laravelusers::partials.date', ['value' => $userActivity[$user->getKey()]['last_login_at'] ?? null])</td>@endif
                            <td><div class="lu-actions">
                                <a class="lu-button lu-success" href="{{ route('users.show', $user->id) }}">@include('laravelusers::partials.icon', ['name' => 'show']) {{ __('laravelusers::ui.show') }}</a>
                                <a class="lu-button" href="{{ route('users.edit', $user->id) }}">@include('laravelusers::partials.icon', ['name' => 'edit']) {{ __('laravelusers::ui.edit') }}</a>
                                @include('laravelusers::modern.delete')
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $columns }}">{{ __('laravelusers::laravelusers.search.no-results') }}</td></tr>
                    @endforelse
                </tbody>
                <tbody id="lu-results" hidden></tbody>
            </table>
        </div>
        <div class="lu-pagination" id="lu-pagination">
            @if(config('laravelusers.showUserCount', true))<span class="lu-muted">{{ $pagintaionEnabled ? __('laravelusers::ui.showing_users', ['first' => $users->firstItem() ?? 0, 'last' => $users->lastItem() ?? 0, 'total' => $users->total()]) : __('laravelusers::ui.total_users', ['count' => $users->count()]) }}</span>@endif
            @if($pagintaionEnabled)
                <nav class="lu-actions" aria-label="{{ __('laravelusers::ui.pagination') }}">
                    @if($users->previousPageUrl())<a class="lu-button lu-secondary" href="{{ $users->previousPageUrl() }}">@include('laravelusers::partials.icon', ['name' => 'back']) {{ __('laravelusers::ui.previous') }}</a>@endif
                    <span class="lu-muted">{{ __('laravelusers::ui.page', ['page' => $users->currentPage(), 'total' => $users->lastPage()]) }}</span>
                    @if($users->nextPageUrl())<a class="lu-button lu-secondary" href="{{ $users->nextPageUrl() }}">{{ __('laravelusers::ui.next') }} @include('laravelusers::partials.icon', ['name' => 'next'])</a>@endif
                </nav>
            @endif
        </div>
    </section>
    @if(config('laravelusers.avatar.enabled', false))<template id="lu-avatar-template"><span class="lu-avatar" aria-hidden="true"><span data-lu-initials></span><svg data-lu-avatar-icon width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg></span></template>@endif
    <template id="lu-row-actions"><div class="lu-actions"><a class="lu-button lu-success" data-lu-show>@include('laravelusers::partials.icon', ['name' => 'show']) {{ __('laravelusers::ui.show') }}</a><a class="lu-button" data-lu-edit>@include('laravelusers::partials.icon', ['name' => 'edit']) {{ __('laravelusers::ui.edit') }}</a><form method="POST" @if(config('laravelusers.confirmDelete', true)) data-lu-confirm @endif>@csrf @method('DELETE')<button class="lu-button lu-danger" type="submit">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.delete') }}</button></form></div></template>
@endsection
