@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
    @lang('laravelusers::laravelusers.showing-all-users')
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
                                @lang('laravelusers::laravelusers.showing-all-users')
                            </span>

                            <div class="btn-group pull-right btn-group-xs">
                                @if(config('laravelusers.softDeletedEnabled'))
                                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-v fa-fw" aria-hidden="true"></i>
                                        <span class="sr-only">
                                            @lang('laravelusers::laravelusers.users-menu-alt')
                                        </span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ route('users.create') }}">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fa fa-fw fa-user-plus" aria-hidden="true"></i>
                                                @endif
                                                @lang('laravelusers::laravelusers.buttons.create-new')
                                            </a>
                                        </li>
                                        <li>
                                            <a href="/users/deleted">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fa fa-fw fa-group" aria-hidden="true"></i>
                                                @endif
                                                @lang('laravelusers::laravelusers.show-deleted-users')
                                            </a>
                                        </li>
                                    </ul>
                                @else
                                    <a href="{{ route('users.create') }}" class="btn btn-default btn-sm pull-right" data-toggle="tooltip" data-placement="left" title="@lang('laravelusers::laravelusers.tooltips.create-new')">
                                        @if(config('laravelusers.fontAwesomeEnabled'))
                                            <i class="fa fa-fw fa-user-plus" aria-hidden="true"></i>
                                        @endif
                                        @lang('laravelusers::laravelusers.buttons.create-new')
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-8 offset-sm-4 col-md-6 offset-md-6 col-lg-5 offset-lg-7 col-xl-4 offset-xl-8">

                                {!! Form::open(['route' => 'search-users', 'method' => 'POST', 'role' => 'form', 'class' => 'needs-validation', 'id' => 'search_users']) !!}
                                    {!! csrf_field() !!}
                                    <div class="input-group mb-3">
                                        {!! Form::text('user_search_box', NULL, ['id' => 'user_search_box', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.search-users-ph'), 'aria-label' => trans('laravelusers::forms.search-users-ph'), 'required' => false]) !!}
                                        <div class="input-group-append">
                                            <a href="#" class="btn btn-warning clear-search" data-toggle="tooltip" title="@lang('laravelusers::laravelusers.tooltips.clear-search')">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fas fa-times" aria-hidden="true"></i>
                                                    <span class="sr-only">
                                                        @lang('laravelusers::laravelusers.tooltips.clear-search')
                                                    </span>
                                                @else
                                                    @lang('laravelusers::laravelusers.tooltips.clear-search')
                                                @endif
                                            </a>
                                            @if(config('laravelusers.fontAwesomeEnabled'))
                                                {!! Form::button('<i class="fas fa-search" aria-hidden="true"></i> <span class="sr-only"> ' . trans('laravelusers::laravelusers.tooltips.submit-search') . ' </span>', ['class' => 'btn btn-secondary', 'type' => 'submit', 'data-toggle' => 'tooltip', 'data-placement' => 'bottom', 'title' => trans('laravelusers::laravelusers.tooltips.submit-search')]) !!}
                                            @else
                                                {!! Form::button(trans('laravelusers::laravelusers.tooltips.submit-search'), ['class' => 'btn btn-secondary', 'type' => 'submit', 'title' => trans('laravelusers::laravelusers.tooltips.submit-search')]) !!}
                                            @endif
                                        </div>
                                    </div>
                                {!! Form::close() !!}

                            </div>
                        </div>
                        <div class="table-responsive users-table">
                            <table class="table table-striped table-sm data-table">
                                <caption id="user_count">
                                    {{ trans_choice('laravelusers::laravelusers.users-table.caption', 1, ['userscount' => $users->count()]) }}
                                </caption>
                                <thead class="thead">
                                    <tr>
                                        <th>@lang('laravelusers::laravelusers.users-table.id')</th>
                                        <th>@lang('laravelusers::laravelusers.users-table.name')</th>
                                        <th class="hidden-xs">@lang('laravelusers::laravelusers.users-table.email')</th>
                                        @if(config('laravelusers.rolesEnabled'))
                                            <th class="hidden-sm hidden-xs">@lang('laravelusers::laravelusers.users-table.role')</th>
                                        @endif
                                        <th class="hidden-sm hidden-xs hidden-md">@lang('laravelusers::laravelusers.users-table.created')</th>
                                        <th class="hidden-sm hidden-xs hidden-md">@lang('laravelusers::laravelusers.users-table.updated')</th>
                                        <th class="no-search no-sort">@lang('laravelusers::laravelusers.users-table.actions')</th>
                                        <th class="no-search no-sort"></th>
                                        <th class="no-search no-sort"></th>
                                    </tr>
                                </thead>
                                <tbody id="users_table">
                                    @foreach($users as $user)
                                        <tr>
                                            <td>{{$user->id}}</td>
                                            <td>{{$user->name}}</td>
                                            <td class="hidden-xs">{{$user->email}}</td>
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
                                            <td class="hidden-sm hidden-xs hidden-md">{{$user->created_at}}</td>
                                            <td class="hidden-sm hidden-xs hidden-md">{{$user->updated_at}}</td>
                                            <td>
                                                {!! Form::open(array('url' => 'users/' . $user->id, 'class' => '', 'data-toggle' => 'tooltip', 'title' => trans('laravelusers::laravelusers.tooltips.delete'))) !!}
                                                    {!! Form::hidden('_method', 'DELETE') !!}
                                                    {!! Form::button(trans('laravelusers::laravelusers.buttons.delete'), array('class' => 'btn btn-danger btn-sm','type' => 'button', 'style' =>'width: 100%;' ,'data-toggle' => 'modal', 'data-target' => '#confirmDelete', 'data-title' => trans('laravelusers::modals.delete_user_title'), 'data-message' => trans('laravelusers::modals.delete_user_message', ['user' => $user->name]))) !!}
                                                {!! Form::close() !!}
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-success btn-block" href="{{ URL::to('users/' . $user->id) }}" data-toggle="tooltip" title="@lang('laravelusers::laravelusers.tooltips.show')">
                                                    @lang('laravelusers::laravelusers.buttons.show')
                                                </a>
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-info btn-block" href="{{ URL::to('users/' . $user->id . '/edit') }}" data-toggle="tooltip" title="@lang('laravelusers::laravelusers.tooltips.edit')">
                                                    @lang('laravelusers::laravelusers.buttons.edit')
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tbody id="search_results"></tbody>
                            </table>

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

    <script>
        $(function() {

            var cardTitle = $('#card_title');
            var usersTable = $('#users_table');
            var resultsContainer = $('#search_results');
            var usersCount = $('#user_count');
            var clearSearchTrigger = $('.clear-search');
            var searchform = $('#search_users');

            searchform.submit(function(e) {
                e.preventDefault();
                resultsContainer.html('');
                usersTable.hide();
                clearSearchTrigger.show();

                var noResulsHtml = '<tr>' +
                                    '<td>@lang("laravelusers::laravelusers.search.no-results")</td>' +
                                    '<td></td>' +
                                    '<td class="hidden-xs"></td>' +
                                    '<td class="hidden-sm hidden-xs"></td>' +
                                    '<td class="hidden-sm hidden-xs hidden-md"></td>' +
                                    '<td class="hidden-sm hidden-xs hidden-md"></td>' +
                                    '<td></td>' +
                                    '<td></td>' +
                                    '<td></td>' +
                                    '</tr>';

                $.ajax({
                    type:'POST',
                    url: "{{ route('search-users') }}",
                    data: searchform.serialize(),
                    success: function (result) {
                        var jsonData = JSON.parse(result);
                        if (jsonData.length != 0) {
                            $.each(jsonData, function(index, val) {
                                var rolesHtml = '';
                                var roleClass = '';
                                $.each(val.roles, function(roleIndex, role) {
                                    if (role.name == "User") {
                                        roleClass = 'primary';
                                    } else if (role.name == "Admin") {
                                        roleClass = 'warning';
                                    } else if (role.name == "Unverified") {
                                        roleClass = 'danger';
                                    } else {
                                        roleClass = 'dark';
                                    };
                                    rolesHtml = '<span class="badge badge-' + roleClass + '">' + role.name + '</span> ';
                                });
                                resultsContainer.append('<tr>' +
                                    '<td>' + val.id + '</td>' +
                                    '<td>' + val.name + '</td>' +
                                    '<td class="hidden-xs">' + val.email + '</td>' +
                                    '@if(config("laravelusers.rolesEnabled"))<td class="hidden-sm hidden-xs"> ' +
                                     rolesHtml  +
                                    '</td>' +
                                    '@endif<td class="hidden-sm hidden-xs hidden-md">' + val.created_at + '</td>' +
                                    '<td class="hidden-sm hidden-xs hidden-md">' + val.updated_at + '</td>' +
                                    '<td>delete</td>' +
                                    '<td>show</td>' +
                                    '<td>edit</td>' +
                                '</tr>');
                            });
                        } else {
                            resultsContainer.append(noResulsHtml);
                        };
                        usersCount.html(jsonData.length + " @lang('laravelusers::laravelusers.search.found-footer')");
                        cardTitle.html("@lang('laravelusers::laravelusers.search.title')");
                    },
                    error: function (response, status, error) {
                        if (response.status === 422) {
                            resultsContainer.append(noResulsHtml);
                            usersCount.html(0 + " @lang('laravelusers::laravelusers.search.found-footer')");
                            cardTitle.html("@lang('laravelusers::laravelusers.search.title')");
                        };
                    },
                });
            });
            clearSearchTrigger.click(function(e) {
                e.preventDefault();
                clearSearchTrigger.hide();
                usersTable.show();
                resultsContainer.html('');
                cardTitle.html("@lang('laravelusers::laravelusers.showing-all-users')");
                usersCount.html("{{ trans_choice('laravelusers::laravelusers.users-table.caption', 1, ['userscount' => $users->count()]) }}");
            });

        });
    </script>

@endsection
