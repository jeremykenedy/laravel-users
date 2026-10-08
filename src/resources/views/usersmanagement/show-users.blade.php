@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
    {!! trans('laravelusers::laravelusers.showing-all-users') !!}
@endsection

@section('template_linked_css')
    @if(config('laravelusers.enabledDatatablesJs'))
        <link rel="stylesheet" type="text/css" href="{{ config('laravelusers.datatablesCssCDN') }}">
    @endif
    @if(config('laravelusers.fontAwesomeEnabled'))
        <link rel="stylesheet" type="text/css" href="{{ config('laravelusers.fontAwesomeCdn') }}">
    @endif
    @include('laravelusers::partials.styles')
    @include('laravelusers::partials.bs-visibility-css')
@endsection

@section('content')
    <div class="container">
        @if(config('laravelusers.enablePackageBootstapAlerts'))
            <div class="row">
                <div class="col-sm-12">
                    @include('laravelusers::partials.form-status')
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {!! trans('laravelusers::laravelusers.showing-all-users') !!}
                            </span>

                            <div class="btn-group pull-right btn-group-xs">
                                @if($hasDeletedUsers ?? false)
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-v fa-fw" aria-hidden="true"></i>
                                        <span class="sr-only">
                                            {!! trans('laravelusers::laravelusers.users-menu-alt') !!}
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ route('users.create') }}">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fa fa-fw fa-user-plus" aria-hidden="true"></i>
                                                @endif
                                                {!! trans('laravelusers::laravelusers.buttons.create-new') !!}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('users.deleted') }}">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fa fa-fw fa-group" aria-hidden="true"></i>
                                                @endif
                                                {!! trans('laravelusers::laravelusers.show-deleted-users') !!}
                                            </a>
                                        </li>
                                    </ul>
                                @else
                                    <a href="{{ route('users.create') }}" class="btn btn-default btn-sm pull-right" data-toggle="tooltip" data-placement="left" title="{!! trans('laravelusers::laravelusers.tooltips.create-new') !!}">
                                        @if(config('laravelusers.fontAwesomeEnabled'))
                                            <i class="fa fa-fw fa-user-plus" aria-hidden="true"></i>
                                        @endif
                                        {!! trans('laravelusers::laravelusers.buttons.create-new') !!}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @include('laravelusers::partials.bulk-actions', ['modern' => false, 'deleted' => false])
        <div class="card-body">

                        @if(config('laravelusers.enableSearchUsers'))
                            @include('laravelusers::partials.search-users-form')
                        @endif

                        <div class="table-responsive users-table">
                            <table data-lu-view="users" class="table table-striped table-sm data-table" data-lu-table>
                                <caption id="user_count">
                                    {!! trans_choice('laravelusers::laravelusers.users-table.caption', 1, ['userscount' => $pagintaionEnabled ? $users->total() : $users->count()]) !!}
                                </caption>
                                <thead class="thead">
                                    <tr>
                                        @if(config('laravelusers.avatar.enabled', false) && (!config('laravelusers.bulkActions', false) || config('laravelusers.enabledDatatablesJs', false)))<th class="no-sort"><span class="lu-sr-only sr-only">{{ __('laravelusers::ui.avatar') }}</span></th>@endif
                                        @if(config('laravelusers.bulkActions', false))@include('laravelusers::partials.select-all')@endif
                                        <th>{!! trans('laravelusers::laravelusers.users-table.id') !!}</th>
                                        <th>{!! trans('laravelusers::laravelusers.users-table.name') !!}</th>
                                        <th class="hidden-xs">{!! trans('laravelusers::laravelusers.users-table.email') !!}</th>
                                        @if(config('laravelusers.rolesEnabled'))
                                            <th class="hidden-sm hidden-xs">{!! trans('laravelusers::laravelusers.users-table.role') !!}</th>
                                        @endif
                                        @if(config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', true))<th>{{ __('laravelusers::ui.presence') }}</th>@endif
                                        @if(config('laravelusers.showCreatedColumn', true))<th class="hidden-sm hidden-xs hidden-md">{!! trans('laravelusers::laravelusers.users-table.created') !!}</th>@endif
                                        @if(config('laravelusers.showUpdatedColumn', true))<th class="hidden-sm hidden-xs hidden-md">{!! trans('laravelusers::laravelusers.users-table.updated') !!}</th>@endif
                                        @if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', true))<th>{{ __('laravelusers::ui.last_login_at') }}</th>@endif
                                        @if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', true))<th>{{ __('laravelusers::ui.login_details') }}</th>@endif
                                        <th class="no-search no-sort">{!! trans('laravelusers::laravelusers.users-table.actions') !!}</th>
                                        <th class="no-search no-sort"></th>
                                        <th class="no-search no-sort"></th>
                                    </tr>
                                </thead>
                                <tbody id="users_table">
                                    @foreach($users as $user)
                                        <tr>
                                            @if(config('laravelusers.avatar.enabled', false))<td>@include('laravelusers::partials.avatar', ['avatar' => $userAvatars[$user->getKey()]])</td>@endif
                                            @if(config('laravelusers.bulkActions', false))<td>@include('laravelusers::partials.select-user')</td>@endif
                                            <td>{{$user->id}}</td>
                                            <td><a href="{{ route('users.show', $user->id) }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.view_user') }}" data-toggle="tooltip" @endif>{{ $user->name }}</a></td>
                                            <td class="hidden-xs">@if(config('laravelusers.emailLinks', true))<a href="mailto:{{ $user->email }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.email_user') }}" data-toggle="tooltip" @endif>{{ $user->email }}</a>@else{{ $user->email }}@endif</td>
                                            @if(config('laravelusers.rolesEnabled'))
                                                <td class="hidden-sm hidden-xs">
                                                    @foreach ($user->roles as $user_role)
                                                        @if ($user_role->name == 'User')
                                                            @php $badgeClass = 'primary' @endphp
                                                        @elseif ($user_role->name == 'Admin')
                                                            @php $badgeClass = 'warning' @endphp
                                                        @elseif ($user_role->name == 'Unverified')
                                                            @php $badgeClass = 'danger' @endphp
                                                        @else
                                                            @php $badgeClass = 'dark' @endphp
                                                        @endif
                                                        <span class="badge badge-{{$badgeClass}}">{{ $user_role->name }}</span>
                                                    @endforeach
                                                </td>
                                            @endif
                                            @if(config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', true))<td data-lu-value="{{ ($userActivity[$user->getKey()]['online'] ?? false) ? 'online' : 'offline' }}">@if(($userActivity[$user->getKey()]['online'] ?? false) === true)<span class="badge badge-success">{{ __('laravelusers::ui.online') }}</span>@endif</td>@endif
                                            @if(config('laravelusers.showCreatedColumn', true))<td class="hidden-sm hidden-xs hidden-md">@include('laravelusers::partials.date', ['value' => $user->created_at])</td>@endif
                                            @if(config('laravelusers.showUpdatedColumn', true))<td class="hidden-sm hidden-xs hidden-md">@include('laravelusers::partials.date', ['value' => $user->updated_at])</td>@endif
                                            @if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', true))<td>@include('laravelusers::partials.date', ['value' => $userActivity[$user->getKey()]['last_login_at'] ?? null])</td>@endif
                                            @if(config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', true))<td>@include('laravelusers::partials.login-details', ['details' => $userActivity[$user->getKey()] ?? []])</td>@endif
                                            <td>
                                                <form method="POST" action="{{ url('users/' . $user->id) }}" data-toggle="tooltip" title="{{ trans('laravelusers::laravelusers.tooltips.delete') }}">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="{{ config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" class="btn btn-danger btn-sm" style="width: 100%;" @if(config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" @endif data-title="{{ trans('laravelusers::modals.delete_user_title') }}" data-message="{{ trans('laravelusers::modals.delete_user_message', ['user' => $user->name]) }}">
                                                        {!! trans('laravelusers::laravelusers.buttons.delete') !!}
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-success btn-block" href="{{ URL::to('users/' . $user->id) }}" data-toggle="tooltip" title="{!! trans('laravelusers::laravelusers.tooltips.show') !!}">
                                                    {!! trans('laravelusers::laravelusers.buttons.show') !!}
                                                </a>
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-info btn-block" href="{{ URL::to('users/' . $user->id . '/edit') }}" data-toggle="tooltip" title="{!! trans('laravelusers::laravelusers.tooltips.edit') !!}">
                                                    {!! trans('laravelusers::laravelusers.buttons.edit') !!}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                @if(config('laravelusers.enableSearchUsers'))
                                    <tbody id="search_results"></tbody>
                                @endif
                            </table>

                            @if(config('laravelusers.showUserCount', true))<p>{{ $pagintaionEnabled ? __('laravelusers::ui.showing_users', ['first' => $users->firstItem() ?? 0, 'last' => $users->lastItem() ?? 0, 'total' => $users->total()]) : __('laravelusers::ui.total_users', ['count' => $users->count()]) }}</p>@endif
                            @if($pagintaionEnabled)
                                {{ $users->links() }}
                            @endif

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    @include('laravelusers::modals.modal-delete')

@endsection

@section('template_scripts')
    @if ((count($users) > config('laravelusers.datatablesJsStartCount')) && config('laravelusers.enabledDatatablesJs'))
        @include('laravelusers::scripts.datatables')
    @endif
    @include('laravelusers::scripts.delete-modal-script')
    @include('laravelusers::scripts.save-modal-script')
    @if(config('laravelusers.tooltipsEnabled'))
        @include('laravelusers::scripts.tooltips')
    @endif
    @if(config('laravelusers.enableSearchUsers'))
        @include('laravelusers::scripts.search-users')
    @endif

@endsection
