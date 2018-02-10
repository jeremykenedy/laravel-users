@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
  @lang('laravelusers::laravelusers.showing-user', ['name' => $user->name])
@endsection

@section('template_linked_css')
    @if(config('laravelusers.enabledDatatablesJs'))
        <link rel="stylesheet" type="text/css" href="{{ config('laravelusers.datatablesCssCDN') }}">
    @endif
    @if(config('laravelusers.fontAwesomeEnabled'))
        <link rel="stylesheet" type="text/css" href="{{ config('laravelusers.fontAwesomeCdn') }}">
    @endif
    @include('laravelusers::partials.styles')
@endsection

@section('content')

  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">

            @lang('laravelusers::laravelusers.showing-user-title', ['name' => $user->name])

            <a href="{{ route('users') }}" class="btn btn-info btn-xs pull-right">
              @if(config('laravelusers.fontAwesomeEnabled'))
                <i class="fa fa-fw fa-mail-reply" aria-hidden="true"></i>
              @endif
              @lang('laravelusers::laravelusers.buttons.back-to-users')
            </a>

          </div>
          <div class="panel-body">

            <div class="well">
              <div class="row">
                <div class="col-sm-12">
                  <h4 class="text-muted margin-top-sm-1 text-center text-left-tablet">
                    {{ $user->name }}
                  </h4>
                  <p class="text-center text-left-tablet">
                    {{ Html::mailto($user->email, $user->email) }}
                  </p>
                  <div class="text-center text-left-tablet margin-bottom-1 row">
                    <div class="col-xs-6">
                      <a href="/users/{{$user->id}}/edit" class="btn btn-sm btn-warning pull-right">
                        @lang('laravelusers::laravelusers.buttons.edit-user')
                      </a>
                    </div>
                    <div class="col-xs-6">
                      {!! Form::open(array('url' => 'users/' . $user->id, 'class' => 'form-inline pull-left')) !!}
                        {!! Form::hidden('_method', 'DELETE') !!}
                        {!! Form::button(trans('laravelusers::laravelusers.buttons.delete-user'), array('class' => 'btn btn-danger btn-sm','type' => 'button', 'data-toggle' => 'modal', 'data-target' => '#confirmDelete', 'data-title' => 'Delete User', 'data-message' => 'Are you sure you want to delete this user?')) !!}
                      {!! Form::close() !!}
                    </div>
                  </div>

                </div>
              </div>
            </div>
            <div class="clearfix"></div>
            <div class="border-bottom"></div>

            <div class="col-xs-5 text-larger">
              <strong>
                @lang('laravelusers::laravelusers.show-user.id')
              </strong>
            </div>
            <div class="col-xs-7">
              {{ $user->id }}
            </div>
            <div class="clearfix"></div>
            <div class="border-bottom"></div>

            @if ($user->name)
              <div class="col-xs-5 text-larger">
                <strong>
                  @lang('laravelusers::laravelusers.show-user.name')
                </strong>
              </div>
              <div class="col-xs-7">
                {{ $user->name }}
              </div>
              <div class="clearfix"></div>
              <div class="border-bottom"></div>
            @endif

            @if ($user->email)
              <div class="col-xs-5 text-larger">
                <strong>
                  @lang('laravelusers::laravelusers.show-user.email')
                </strong>
              </div>
              <div class="col-xs-7">
                {{ Html::mailto($user->email, $user->email) }}
              </div>
              <div class="clearfix"></div>
              <div class="border-bottom"></div>
            @endif

            @if(config('laravelusers.rolesEnabled'))
              <div class="col-xs-5 text-larger">
                <strong>
                  {{ trans('laravelusers::laravelusers.show-user.labelRole') }}
                </strong>
              </div>
              <div class="col-xs-7">
                @foreach ($user->roles as $user_role)
                  @if ($user_role->name == 'User')
                    @php $labelClass = 'primary' @endphp
                  @elseif ($user_role->name == 'Admin')
                    @php $labelClass = 'warning' @endphp
                  @elseif ($user_role->name == 'Unverified')
                    @php $labelClass = 'danger' @endphp
                  @else
                    @php $labelClass = 'default' @endphp
                  @endif
                  <span class="label label-{{$labelClass}}">{{ $user_role->name }}</span>
                @endforeach
              </div>
              <div class="clearfix"></div>
              <div class="border-bottom"></div>

              <div class="col-xs-5 text-larger">
                <strong>
                  {!! trans_choice('laravelusers::laravelusers.show-user.labelAccessLevel', 1) !!}
                </strong>
              </div>
              <div class="col-xs-7">
                @if($user->level() >= 5)
                  <span class="label label-primary margin-half margin-left-0">5</span>
                @endif
                @if($user->level() >= 4)
                   <span class="label label-info margin-half margin-left-0">4</span>
                @endif
                @if($user->level() >= 3)
                  <span class="label label-success margin-half margin-left-0">3</span>
                @endif
                @if($user->level() >= 2)
                  <span class="label label-warning margin-half margin-left-0">2</span>
                @endif
                @if($user->level() >= 1)
                  <span class="label label-default margin-half margin-left-0">1</span>
                @endif
              </div>
              <div class="clearfix"></div>
              <div class="border-bottom"></div>
            @endif

            @if ($user->created_at)
              <div class="col-xs-5 text-larger">
                <strong>
                  @lang('laravelusers::laravelusers.show-user.created')
                </strong>
              </div>
              <div class="col-xs-7">
                {{ $user->created_at }}
              </div>
              <div class="clearfix"></div>
              <div class="border-bottom"></div>
            @endif

            @if ($user->updated_at)
              <div class="col-xs-5 text-larger">
                <strong>
                  @lang('laravelusers::laravelusers.show-user.updated')
                </strong>
              </div>
              <div class="col-xs-7">
                {{ $user->updated_at }}
              </div>
              <div class="clearfix"></div>
              <div class="border-bottom"></div>
            @endif

          </div>
        </div>
      </div>
    </div>
  </div>

  @include('laravelusers::modals.modal-delete')

@endsection

@section('template_scripts')
  @include('laravelusers::scripts.delete-modal-script')
@endsection
