@extends('laravelusers::modern.page')
@php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
@section('template_title', __('laravelusers::laravelusers.showing-all-users'))
@section('users_content')
    <header class="lu-heading">
        <div><h1>{{ __('laravelusers::app.nav.users') }}</h1><p class="lu-muted">{{ __('laravelusers::ui.intro') }}</p></div>
        <a class="lu-button {{ $tailwind ? 'lu:inline-flex lu:items-center lu:rounded-lg' : 'btn btn-primary' }}" href="{{ route('users.create') }}">{{ __('laravelusers::laravelusers.create-new-user') }}</a>
    </header>
    <section class="lu-panel {{ $tailwind ? 'lu:rounded-xl lu:border lu:shadow-sm' : 'card' }}" aria-label="{{ __('laravelusers::app.nav.users') }}">
        @if(config('laravelusers.enableSearchUsers'))
            <form class="lu-search" id="lu-search" action="{{ route('search-users') }}" method="POST">
                @csrf
                <div class="lu-search-field"><label for="user_search_box">{{ __('laravelusers::forms.search-users-ph') }}</label><input class="lu-input {{ $tailwind ? 'lu:w-full lu:rounded-lg' : 'form-control' }}" type="search" id="user_search_box" name="user_search_box" maxlength="255" required></div>
                <button class="lu-button" type="submit">{{ __('laravelusers::ui.search') }}</button>
                <button class="lu-button lu-secondary" type="reset">{{ __('laravelusers::ui.clear') }}</button>
            </form>
            <p id="lu-search-status" class="lu-pad lu-muted" role="status" hidden></p>
        @endif
        <div class="lu-scroll {{ $tailwind ? 'lu:overflow-x-auto' : 'table-responsive' }}">
            <table class="{{ $tailwind ? 'lu:w-full lu:text-left' : 'table' }}">
                <caption>{{ __('laravelusers::ui.directory') }}</caption>
                <thead><tr>
                    @foreach(['id', 'name', 'email'] as $column)<th scope="col">{{ __('laravelusers::laravelusers.users-table.'.$column) }}</th>@endforeach
                    @if(config('laravelusers.rolesEnabled'))<th scope="col">{{ __('laravelusers::laravelusers.users-table.role') }}</th>@endif
                    <th scope="col">{{ __('laravelusers::laravelusers.users-table.actions') }}</th>
                </tr></thead>
                <tbody id="lu-users">
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td><td><a href="{{ route('users.show', $user->id) }}">{{ $user->name }}</a></td><td>{{ $user->email }}</td>
                            @if(config('laravelusers.rolesEnabled'))<td>{{ $user->roles->pluck('name')->implode(', ') }}</td>@endif
                            <td><div class="lu-actions">
                                <a class="lu-button lu-secondary" href="{{ route('users.edit', $user->id) }}">{{ __('laravelusers::ui.edit') }}</a>
                                @include('laravelusers::modern.delete')
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ config('laravelusers.rolesEnabled') ? 5 : 4 }}">{{ __('laravelusers::laravelusers.search.no-results') }}</td></tr>
                    @endforelse
                </tbody>
                <tbody id="lu-results" hidden></tbody>
            </table>
        </div>
        @if($pagintaionEnabled)
            <nav class="lu-pagination" id="lu-pagination" aria-label="{{ __('laravelusers::ui.pagination') }}">
                @if($users->previousPageUrl())<a class="lu-button lu-secondary" href="{{ $users->previousPageUrl() }}">{{ __('laravelusers::ui.previous') }}</a>@endif
                <span class="lu-muted">{{ __('laravelusers::ui.page', ['page' => $users->currentPage(), 'total' => $users->lastPage()]) }}</span>
                @if($users->nextPageUrl())<a class="lu-button lu-secondary" href="{{ $users->nextPageUrl() }}">{{ __('laravelusers::ui.next') }}</a>@endif
            </nav>
        @endif
    </section>
@endsection
