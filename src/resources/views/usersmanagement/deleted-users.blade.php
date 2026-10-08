@extends(config('laravelusers.laravelUsersBladeExtended'))
@section('template_title', __('laravelusers::laravelusers.show-deleted-users'))
@section('template_linked_css')
    @include('laravelusers::partials.styles')
    @include('laravelusers::partials.bs-visibility-css')
@endsection
@section('content')
    <div class="container users-table">
        @if(config('laravelusers.enablePackageBootstapAlerts'))@include('laravelusers::partials.form-status')@endif
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center"><h1 class="h5 mb-0">{{ __('laravelusers::laravelusers.show-deleted-users') }}</h1><a class="btn btn-secondary" href="{{ route('users') }}">{{ __('laravelusers::ui.back') }}</a></header>
            @include('laravelusers::partials.bulk-actions', ['modern' => false, 'deleted' => true])
        <div class="card-body table-responsive">
                <table data-lu-view="deleted" class="table table-striped" data-lu-table>
                    <caption>{{ __('laravelusers::laravelusers.show-deleted-users') }}</caption>
                    <thead><tr>@if(config('laravelusers.avatar.enabled', false))<th data-lu-no-sort>{{ __('laravelusers::ui.avatar') }}</th>@endif
                            @if(config('laravelusers.bulkActions', false))@include('laravelusers::partials.select-all')@endif<th>{{ __('laravelusers::laravelusers.users-table.id') }}</th><th>{{ __('laravelusers::laravelusers.users-table.name') }}</th><th>{{ __('laravelusers::laravelusers.users-table.email') }}</th><th>{{ __('laravelusers::ui.deleted_at') }}</th><th class="no-sort">{{ __('laravelusers::laravelusers.users-table.actions') }}</th></tr></thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>@if(config('laravelusers.avatar.enabled', false))<td>@include('laravelusers::partials.avatar', ['avatar' => $userAvatars[$user->getKey()]])</td>@endif
                            @if(config('laravelusers.bulkActions', false))<td>@include('laravelusers::partials.select-user')</td>@endif<td>{{ $user->id }}</td><td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>@include('laravelusers::partials.date', ['value' => $user->deleted_at])</td><td><div class="d-flex">
                                <form method="POST" action="{{ route('users.restore', $user->id) }}" class="mr-2">@csrf<button class="btn btn-success" type="submit" title="{{ __('laravelusers::ui.restore') }}">@if(config('laravelusers.fontAwesomeEnabled'))<i class="fas fa-undo" aria-hidden="true"></i>@endif {{ __('laravelusers::ui.restore') }}</button></form>
                                <form method="POST" action="{{ route('users.force-destroy', $user->id) }}">@csrf @method('DELETE')<button class="btn btn-danger" type="{{ config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" @if(config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" @endif data-title="{{ __('laravelusers::ui.permanently_delete') }}" data-message="{{ __('laravelusers::ui.confirm_delete', ['name' => $user->name]) }}">@if(config('laravelusers.fontAwesomeEnabled'))<i class="fas fa-trash-alt" aria-hidden="true"></i>@endif {{ __('laravelusers::ui.permanently_delete') }}</button></form>
                            </div></td></tr>
                        @empty
                            <tr><td colspan="{{ 5 + (int) config('laravelusers.avatar.enabled', false) + (int) config('laravelusers.bulkActions', false) }}">{{ __('laravelusers::laravelusers.search.no-results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if(config('laravelusers.showUserCount', true))<p>{{ __('laravelusers::ui.total_users', ['count' => $pagintaionEnabled ? $users->total() : $users->count()]) }}</p>@endif
                @if($pagintaionEnabled){{ $users->links() }}@endif
            </div>
        </section>
    </div>
    @include('laravelusers::modals.modal-delete')
@endsection
@section('template_scripts')
    @include('laravelusers::scripts.delete-modal-script')
    @if(config('laravelusers.tooltipsEnabled'))@include('laravelusers::scripts.tooltips')@endif
@endsection
