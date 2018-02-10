@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
  @lang('laravelusers::laravelusers.editing-user', ['name' => $user->name])
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

            @lang('laravelusers::laravelusers.editing-user', ['name' => $user->name])

            <a href="{{ url('/users/' . $user->id) }}" class="btn btn-primary btn-xs pull-right" style="margin-left: 1em;">
              @if(config('laravelusers.fontAwesomeEnabled'))
                <i class="fa fa-fw fa-mail-reply" aria-hidden="true"></i>
              @endif
              @lang('laravelusers::laravelusers.buttons.back-to-user')
            </a>

            <a href="{{ route('users') }}" class="btn btn-info btn-xs pull-right">
              @if(config('laravelusers.fontAwesomeEnabled'))
                <i class="fa fa-fw fa-mail-reply" aria-hidden="true"></i>
              @endif
              @lang('laravelusers::laravelusers.buttons.back-to-users')
            </a>

          </div>

          {!! Form::open(array('route' => ['users.update', $user->id], 'method' => 'PUT', 'role' => 'form')) !!}

            {!! csrf_field() !!}

            <div class="panel-body">

              @include('laravelusers::partials.form-status')

              <div class="form-group has-feedback row {{ $errors->has('name') ? ' has-error ' : '' }}">
                {!! Form::label('name', trans('laravelusers::forms.ph-username'), array('class' => 'col-md-3 control-label')); !!}
                <div class="col-md-9">
                  <div class="input-group">
                    {!! Form::text('name', $user->name, array('id' => 'name', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.ph-username'))) !!}
                    <label class="input-group-addon" for="name">
                      @if(config('laravelusers.fontAwesomeEnabled'))
                        <i class="fa fa-fw fa-user }}" aria-hidden="true"></i>
                      @else
                        @lang('laravelusers::forms.ph-username')
                      @endif
                    </label>
                  </div>
                </div>
              </div>

              <div class="form-group has-feedback row {{ $errors->has('email') ? ' has-error ' : '' }}">
                {!! Form::label('email', trans('laravelusers::forms.label-useremail'), array('class' => 'col-md-3 control-label')); !!}
                <div class="col-md-9">
                  <div class="input-group">
                    {!! Form::text('email', $user->email, array('id' => 'email', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.ph-useremail'))) !!}
                    <label class="input-group-addon" for="email">
                      @if(config('laravelusers.fontAwesomeEnabled'))
                        <i class="fa fa-fw fa-envelope " aria-hidden="true"></i>
                      @else
                        @lang('laravelusers::forms.label-useremail')
                      @endif
                    </label>
                  </div>
                </div>
              </div>

              @if($rolesEnabled)
                <div class="form-group has-feedback row {{ $errors->has('role') ? ' has-error ' : '' }}">
                  {!! Form::label('role', trans('laravelusers::forms.create_user_label_role'), array('class' => 'col-md-3 control-label')); !!}
                  <div class="col-md-9">
                    <div class="input-group">
                      <select class="form-control" name="role" id="role">
                        <option value="">{{ trans('laravelusers::forms.create_user_ph_role') }}</option>
                        @if ($roles->count())
                          @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ $currentRole->id == $role->id ? 'selected="selected"' : '' }}>{{ $role->name }}</option>
                          @endforeach
                        @endif
                      </select>
                      <label class="input-group-addon" for="role"><i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_role') }}" aria-hidden="true"></i></label>
                    </div>
                  </div>
                </div>
              @endif

              <div class="pw-change-container">
                <div class="form-group has-feedback row {{ $errors->has('password') ? ' has-error ' : '' }}">
                  {!! Form::label('password', trans('laravelusers::forms.create_user_label_password'), array('class' => 'col-md-3 control-label')); !!}
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
                  </div>
                </div>

                <div class="form-group has-feedback row {{ $errors->has('password_confirmation') ? ' has-error ' : '' }}">
                  {!! Form::label('password_confirmation', trans('laravelusers::forms.create_user_label_pw_confirmation'), array('class' => 'col-md-3 control-label')); !!}
                  <div class="col-md-9">
                    <div class="input-group">
                      {!! Form::password('password_confirmation', array('id' => 'password_confirmation', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.create_user_ph_pw_confirmation'))) !!}
                      <label class="input-group-addon" for="password_confirmation">
                        @if(config('laravelusers.fontAwesomeEnabled'))
                          <i class="fa fa-fw {{ trans('laravelusers::forms.create_user_icon_pw_confirmation') }}" aria-hidden="true"></i>
                        @else
                          @lang('laravelusers::forms.create_user_ph_pw_confirmation')
                        @endif
                      </label>
                    </div>
                  </div>
                </div>
              </div>

            </div>
            <div class="panel-footer">
              <div class="row">

                <div class="col-xs-6">
                  <a href="#" class="btn btn-default btn-block margin-bottom-1 btn-change-pw" title="Change Password">
                    <i class="fa fa-fw fa-lock" aria-hidden="true"></i>
                    <span></span> @lang('laravelusers::forms.change-pw')
                  </a>
                </div>

                <div class="col-xs-6">
                  {!! Form::button(trans('laravelusers::forms.save-changes'), array('class' => 'btn btn-success btn-block margin-bottom-1 btn-save','type' => 'button', 'data-toggle' => 'modal', 'data-target' => '#confirmSave', 'data-title' => trans('modals.edit_user__modal_text_confirm_title'), 'data-message' => trans('modals.edit_user__modal_text_confirm_message'))) !!}
                </div>

              </div>
            </div>

          {!! Form::close() !!}

        </div>
      </div>
    </div>
  </div>

  @include('laravelusers::modals.modal-save')
  @include('laravelusers::modals.modal-delete')

@endsection

@section('template_scripts')
  @include('laravelusers::scripts.delete-modal-script')
  @include('laravelusers::scripts.save-modal-script')
  @include('laravelusers::scripts.check-changed')
@endsection
