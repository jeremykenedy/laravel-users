@extends('laravelusers::modern.page')
@section('template_title', __('laravelusers::laravelusers.show-deleted-users'))
@section('users_content')
    <section class="lu-panel">
        <header class="lu-heading lu-card-heading"><h1>{{ __('laravelusers::laravelusers.show-deleted-users') }}</h1><a class="lu-button lu-secondary" href="{{ route('users') }}">@include('laravelusers::partials.icon', ['name' => 'back']) {{ __('laravelusers::ui.back') }}</a></header>
        @include('laravelusers::partials.bulk-actions', ['modern' => true, 'deleted' => true])
        <div class="lu-scroll">
            <table data-lu-view="deleted" data-lu-table>
                <caption>{{ __('laravelusers::laravelusers.show-deleted-users') }}</caption>
                <thead><tr>@if(config('laravelusers.avatar.enabled', false) && (!config('laravelusers.bulkActions', false) || config('laravelusers.enabledDatatablesJs', false)))<th data-lu-no-sort><span class="lu-sr-only sr-only">{{ __('laravelusers::ui.avatar') }}</span></th>@endif
                            @if(config('laravelusers.bulkActions', false))@include('laravelusers::partials.select-all')@endif<th scope="col">{{ __('laravelusers::laravelusers.users-table.id') }}</th><th scope="col">{{ __('laravelusers::laravelusers.users-table.name') }}</th><th scope="col">{{ __('laravelusers::laravelusers.users-table.email') }}</th><th scope="col">{{ __('laravelusers::ui.deleted_at') }}</th>@if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', true))<th>{{ __('laravelusers::ui.login_details') }}</th>@endif<th scope="col" data-lu-no-sort>{{ __('laravelusers::laravelusers.users-table.actions') }}</th></tr></thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>@if(config('laravelusers.avatar.enabled', false))<td>@include('laravelusers::partials.avatar', ['avatar' => $userAvatars[$user->getKey()]])</td>@endif
                            @if(config('laravelusers.bulkActions', false))<td>@include('laravelusers::partials.select-user')</td>@endif<td>{{ $user->id }}</td><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>@include('laravelusers::partials.date', ['value' => $user->deleted_at])</td>@if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', true))<td>@include('laravelusers::partials.login-details', ['details' => $userActivity[$user->getKey()] ?? []])</td>@endif<td><div class="lu-actions">
                            <form method="POST" action="{{ route('users.restore', $user->id) }}">@csrf<button type="submit" class="lu-button lu-success">@include('laravelusers::partials.icon', ['name' => 'back']) {{ __('laravelusers::ui.restore') }}</button></form>
                            <form method="POST" action="{{ route('users.force-destroy', $user->id) }}" @if(config('laravelusers.confirmDelete', true)) data-lu-confirm="{{ __('laravelusers::ui.confirm_delete', ['name' => $user->name]) }}" @endif>@csrf @method('DELETE')<button type="submit" class="lu-button lu-danger">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.permanently_delete') }}</button></form>
                        </div></td></tr>
                    @empty
                        <tr><td colspan="{{ 5 + (int) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', true)) + (int) config('laravelusers.avatar.enabled', false) + (int) config('laravelusers.bulkActions', false) }}">{{ __('laravelusers::laravelusers.search.no-results') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="lu-pagination">
            @if(config('laravelusers.showUserCount', true))<span>{{ __('laravelusers::ui.total_users', ['count' => $pagintaionEnabled ? $users->total() : $users->count()]) }}</span>@endif
            @if($pagintaionEnabled)<nav class="lu-actions" aria-label="{{ __('laravelusers::ui.pagination') }}">@if($users->previousPageUrl())<a class="lu-button lu-secondary" href="{{ $users->previousPageUrl() }}">{{ __('laravelusers::ui.previous') }}</a>@endif<span>{{ __('laravelusers::ui.page', ['page' => $users->currentPage(), 'total' => $users->lastPage()]) }}</span>@if($users->nextPageUrl())<a class="lu-button lu-secondary" href="{{ $users->nextPageUrl() }}">{{ __('laravelusers::ui.next') }}</a>@endif</nav>@endif
        </div>
    </section>
@endsection
