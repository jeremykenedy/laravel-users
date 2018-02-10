@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
  @lang('laravelusers::laravelusers.create-new-user')
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
      <div class="col-md-10 col-md-offset-1">
        <div class="panel panel-default">
          <div class="panel-heading">

            @lang('laravelusers::laravelusers.create-new-user')

            <a href="{{ route('users') }}" class="btn btn-info btn-xs pull-right">
              @if(config('laravelusers.fontAwesomeEnabled'))
                <i class="fa fa-fw fa-mail-reply" aria-hidden="true"></i>
              @endif
              @lang('laravelusers::laravelusers.buttons.back-to-users')
            </a>

          </div>
          <div class="panel-body">

            @include('laravelusers::partials.form-status')

            {!! Form::open(array('route' => 'users.store', 'method' => 'POST', 'role' => 'form')) !!}

              {!! csrf_field() !!}

                <div class="form-group has-feedback row {{ $errors->has('email') ? ' has-error ' : '' }}">
                @if(config('laravelusers.fontAwesomeEnabled'))
                  {!! Form::label('email', trans('laravelusers::forms.create_user_label_email'), array('class' => 'col-md-3 control-label')); !!}
                @endif
                <div class="col-md-9">
                  <div class="input-group">
                    {!! Form::text('email', NULL, array('id' => 'email', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.create_user_ph_email'))) !!}
                    <label class="input-group-addon" for="email">
                      @if(config('laravelusers.fontAwesomeEnabled'))
                        <i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_email') }}" aria-hidden="true"></i>
                      @else
                        @lang('laravelusers::forms.create_user_label_email')
                      @endif
                    </label>
                  </div>
                  @if ($errors->has('email'))
                    <span class="help-block">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                  @endif
                </div>
              </div>

              <div class="form-group has-feedback row {{ $errors->has('name') ? ' has-error ' : '' }}">
                @if(config('laravelusers.fontAwesomeEnabled'))
                  {!! Form::label('name', trans('laravelusers::forms.create_user_label_username'), array('class' => 'col-md-3 control-label')); !!}
                @endif
                <div class="col-md-9">
                  <div class="input-group">
                    {!! Form::text('name', NULL, array('id' => 'name', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.create_user_ph_username'))) !!}
                    <label class="input-group-addon" for="name">
                      @if(config('laravelusers.fontAwesomeEnabled'))
                        <i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_username') }}" aria-hidden="true"></i>
                      @else
                        @lang('laravelusers::forms.create_user_label_username')
                      @endif
                    </label>
                  </div>
                  @if ($errors->has('name'))
                    <span class="help-block">
                        <strong>{{ $errors->first('name') }}</strong>
                    </span>
                  @endif
                </div>
              </div>

              @if($rolesEnabled)
                <div class="form-group has-feedback row {{ $errors->has('role') ? ' has-error ' : '' }}">
                  @if(config('laravelusers.fontAwesomeEnabled'))
                    {!! Form::label('role', trans('laravelusers::forms.create_user_label_role'), array('class' => 'col-md-3 control-label')); !!}
                  @endif
                  <div class="col-md-9">
                    <div class="input-group">
                      <select class="form-control" name="role" id="role">
                        <option value="">{{ trans('laravelusers::forms.create_user_ph_role') }}</option>
                        @if ($roles->count())
                          @foreach($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                          @endforeach
                        @endif
                      </select>
                      <label class="input-group-addon" for="role">
                        @if(config('laravelusers.fontAwesomeEnabled'))
                          <i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_role') }}" aria-hidden="true"></i>
                        @else
                          @lang('laravelusers::forms.create_user_label_username')
                        @endif
                      </label>
                    </div>
                    @if ($errors->has('role'))
                      <span class="help-block">
                          <strong>{{ $errors->first('role') }}</strong>
                      </span>
                    @endif
                  </div>
                </div>
              @endif

              <div class="form-group has-feedback row {{ $errors->has('password') ? ' has-error ' : '' }}">
                @if(config('laravelusers.fontAwesomeEnabled'))
                  {!! Form::label('password', trans('laravelusers::forms.create_user_label_password'), array('class' => 'col-md-3 control-label')); !!}
                @endif
                <div class="col-md-9">
                  <div class="input-group">
                    {!! Form::password('password', array('id' => 'password', 'class' => 'form-control ', 'placeholder' => trans('laravelusers::forms.create_user_ph_password'))) !!}
                    <label class="input-group-addon" for="password">
                      @if(config('laravelusers.fontAwesomeEnabled'))
                        <i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_password') }}" aria-hidden="true"></i>
                      @else
                        @lang('laravelusers::forms.create_user_label_password')
                      @endif
                    </label>
                  </div>
                  @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                  @endif
                </div>
              </div>

              <div class="form-group has-feedback row {{ $errors->has('password_confirmation') ? ' has-error ' : '' }}">
                @if(config('laravelusers.fontAwesomeEnabled'))
                  {!! Form::label('password_confirmation', trans('laravelusers::forms.create_user_label_pw_confirmation'), array('class' => 'col-md-3 control-label')); !!}
                @endif
                <div class="col-md-9">
                  <div class="input-group">
                    {!! Form::password('password_confirmation', array('id' => 'password_confirmation', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.create_user_ph_pw_confirmation'))) !!}
                    <label class="input-group-addon" for="password_confirmation">
                      @if(config('laravelusers.fontAwesomeEnabled'))
                        <i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_pw_confirmation') }}" aria-hidden="true"></i>
                      @else
                        @lang('laravelusers::forms.create_user_label_pw_confirmation')
                      @endif
                    </label>
                  </div>
                  @if ($errors->has('password_confirmation'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                    </span>
                  @endif
                </div>
              </div>

              {!! Form::button(trans('laravelusers::forms.create_user_button_text'), array('class' => 'btn btn-success btn-flat margin-bottom-1 pull-right','type' => 'submit', )) !!}

            {!! Form::close() !!}

          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('template_scripts')
@endsection
