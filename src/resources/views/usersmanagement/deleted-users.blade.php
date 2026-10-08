@extends(config('laravelusers.laravelUsersBladeExtended'))
@section('template_title', __('laravelusers::laravelusers.show-deleted-users'))
@section('template_linked_css')
    @if(config('laravelusers.fontAwesomeEnabled'))
        <link rel="stylesheet" type="text/css" href="{{ config('laravelusers.fontAwesomeCdn') }}">
    @endif
    @include('laravelusers::partials.styles')
    @include('laravelusers::partials.bs-visibility-css')
@endsection
@section('content')
    <div class="container users-table">
        @if(config('laravelusers.enablePackageBootstapAlerts'))@include('laravelusers::partials.form-status')@endif
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center"><h1 class="h5 mb-0">{{ __('laravelusers::laravelusers.show-deleted-users') }}</h1><a class="btn btn-secondary" href="{{ route('users') }}">@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.back') }}</a></header>
            @include('laravelusers::partials.bulk-actions', ['modern' => false, 'deleted' => true])
        <div class="card-body table-responsive">
                <table data-lu-view="deleted" class="table table-striped" data-lu-table>
                    <caption>{{ __('laravelusers::laravelusers.show-deleted-users') }}</caption>
                    <thead><tr>@if(config('laravelusers.avatar.enabled', false) && (!config('laravelusers.bulkActions', false) || config('laravelusers.enabledDatatablesJs', false)))<th data-lu-no-sort><span class="lu-sr-only sr-only">{{ __('laravelusers::ui.avatar') }}</span></th>@endif
                            @if(config('laravelusers.bulkActions', false))@include('laravelusers::partials.select-all')@endif<th>{{ __('laravelusers::laravelusers.users-table.id') }}</th><th>{{ __('laravelusers::laravelusers.users-table.name') }}</th><th>{{ __('laravelusers::laravelusers.users-table.email') }}</th>@if(config('laravelusers.rolesEnabled'))<th>{{ __('laravelusers::laravelusers.users-table.role') }}</th>@endif
                            @if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false))<th>{{ __('laravelusers::ui.last_login_at') }}</th>@endif<th>{{ __('laravelusers::ui.deleted_at') }}</th>@if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false))<th>{{ __('laravelusers::ui.login_details') }}</th>@endif<th class="no-sort">{{ __('laravelusers::laravelusers.users-table.actions') }}</th></tr></thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr @include('laravelusers::partials.appearance-attributes', ['appearance' => $userAppearance[$user->getKey()] ?? null])>@if(config('laravelusers.avatar.enabled', false))<td>@include('laravelusers::partials.avatar', ['avatar' => $userAvatars[$user->getKey()]])</td>@endif
                            @if(config('laravelusers.bulkActions', false))<td data-lu-selection-cell>@include('laravelusers::partials.select-user')</td>@endif<td>{{ $user->id }}</td><td>{{ $user->name }}</td><td>@if(config('laravelusers.emailLinks', false))<a href="mailto:{{ $user->email }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.email_user') }}" @endif>{{ $user->email }}</a>@else{{ $user->email }}@endif</td>@if(config('laravelusers.rolesEnabled'))<td>{{ $user->roles->pluck('name')->implode(', ') }}</td>@endif
                            @if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false))<td>@include('laravelusers::partials.date', ['value' => $userActivity[$user->getKey()]['last_login_at'] ?? null, 'empty' => __('laravelusers::ui.no_logins')])</td>@endif<td>@include('laravelusers::partials.date', ['value' => $user->deleted_at])</td>@if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false))<td>@include('laravelusers::partials.login-details', ['details' => $userActivity[$user->getKey()] ?? []])</td>@endif<td><div class="lu-actions">
                                @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('restore_users'))<form method="POST" action="{{ route('users.restore', $user->id) }}">@csrf<button class="btn btn-success" type="submit" aria-label="{{ __('laravelusers::ui.restore') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.restore') }}" @endif>@if(config('laravelusers.fontAwesomeEnabled', true))<i class="fas fa-undo" aria-hidden="true"></i>@endif <span>{{ __('laravelusers::ui.restore') }}</span></button></form>@endif
                                @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('force_delete'))<form method="POST" action="{{ route('users.force-destroy', $user->id) }}">@csrf @method('DELETE')<button class="btn btn-danger" type="{{ config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" @if(config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" @endif data-title="{{ __('laravelusers::ui.permanently_delete') }}" data-message="{{ __('laravelusers::ui.confirm_delete', ['name' => $user->name]) }}" aria-label="{{ __('laravelusers::ui.permanently_delete') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.permanently_delete') }}" @endif>@if(config('laravelusers.fontAwesomeEnabled', true))<i class="fas fa-trash-alt" aria-hidden="true"></i>@endif <span>{{ __('laravelusers::ui.permanently_delete') }}</span></button></form>@endif
                                            @if(config('laravelusers.settings.enabled', false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_deleted'))<a class="btn btn-info" href="{{ route('users.deleted.edit', $user->id) }}" aria-label="{{ __('laravelusers::ui.edit') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.edit') }}" @endif>@include('laravelusers::partials.icon', ['name' => 'edit']) <span>{{ __('laravelusers::ui.edit') }}</span></a>@endif
                            <x-laravelusers::email-actions :user="$user" :compact="true" :deleted="true" />
                            </div></td></tr>
                        @empty
                            <tr><td colspan="{{ 5 + (int) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false)) + (int) config('laravelusers.rolesEnabled') + (int) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false)) + (int) config('laravelusers.avatar.enabled', false) + (int) config('laravelusers.bulkActions', false) }}">{{ __('laravelusers::laravelusers.search.no-results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if(config('laravelusers.showUserCount', false))<p>{{ __('laravelusers::ui.total_users', ['count' => $pagintaionEnabled ? $users->total() : $users->count()]) }}</p>@endif
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
