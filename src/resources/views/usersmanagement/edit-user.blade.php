@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
    {!! trans('laravelusers::laravelusers.editing-user', ['name' => $user->name]) !!}
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
                <div class="col-lg-10 offset-lg-1">
                    @include('laravelusers::partials.form-status')
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            {!! trans('laravelusers::laravelusers.editing-user', ['name' => $user->name]) !!}
                            <div class="pull-right">
                                <a href="{{ route('users') }}" class="btn btn-light btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{!! trans('laravelusers::laravelusers.tooltips.back-users') !!}">
                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                        <i class="fas fa-fw fa-reply-all" aria-hidden="true"></i>
                                    @endif
                                    {!! trans('laravelusers::laravelusers.buttons.back-to-users') !!}
                                </a>
                                <a href="{{ url('/users/' . $user->id) }}" class="btn btn-light btn-sm float-right" data-toggle="tooltip" data-placement="left" title="{!! trans('laravelusers::laravelusers.tooltips.back-user') !!}">
                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                        <i class="fas fa-fw fa-reply" aria-hidden="true"></i>
                                    @endif
                                    {!! trans('laravelusers::laravelusers.buttons.back-to-user') !!}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {!! Form::open(array('route' => ['users.update', $user->id], 'method' => 'PUT', 'role' => 'form', 'class' => 'needs-validation')) !!}
                            {!! csrf_field() !!}
                            <div class="form-group has-feedback row {{ $errors->has('name') ? ' has-error ' : '' }}">
                                @if(config('laravelusers.fontAwesomeEnabled'))
                                    {!! Form::label('name', trans('laravelusers::forms.create_user_label_username'), array('class' => 'col-md-3 control-label')); !!}
                                @endif
                                <div class="col-md-9">
                                    <div class="input-group">
                                        {!! Form::text('name', $user->name, array('id' => 'name', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.create_user_ph_username'))) !!}
                                        <div class="input-group-append">
                                            <label class="input-group-text" for="name">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fa fa-fw {!! trans('laravelusers::forms.create_user_icon_username') !!}" aria-hidden="true"></i>
                                                @else
                                                    {!! trans('laravelusers::forms.create_user_label_username') !!}
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                    @if ($errors->has('name'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('name') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group has-feedback row {{ $errors->has('email') ? ' has-error ' : '' }}">
                                @if(config('laravelusers.fontAwesomeEnabled'))
                                    {!! Form::label('email', trans('laravelusers::forms.create_user_label_email'), array('class' => 'col-md-3 control-label')); !!}
                                @endif
                                <div class="col-md-9">
                                    <div class="input-group">
                                        {!! Form::text('email', $user->email, array('id' => 'email', 'class' => 'form-control', 'placeholder' => trans('laravelusers::forms.create_user_ph_email'))) !!}
                                        <div class="input-group-append">
                                            <label for="email" class="input-group-text">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="fa fa-fw {!! trans('laravelusers::forms.create_user_icon_email') !!}" aria-hidden="true"></i>
                                                @else
                                                    {!! trans('laravelusers::forms.create_user_label_email') !!}
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                    @if ($errors->has('email'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('email') }}</strong>
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
                                        <select class="custom-select form-control" name="role[]" id="role" multiple>
                                            <option value="">{!! trans('laravelusers::forms.create_user_ph_role') !!}</option>
                                            @if ($roles)
                                                @foreach($roles as $role)
                                                    @if ($currentRole)
                                                        <option value="{{ $role->id }}" {{ in_array($role->id ,$currentRole) ? 'selected="selected"' : '' }}>{{ $role->name }}</option>
                                                    @else
                                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                        <div class="input-group-append">
                                            <label class="input-group-text" for="role">
                                                @if(config('laravelusers.fontAwesomeEnabled'))
                                                    <i class="{!! trans('laravelusers::forms.create_user_icon_role') !!}" aria-hidden="true"></i>
                                                @else
                                                    {!! trans('laravelusers::forms.create_user_label_username') !!}
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                    @if ($errors->has('role'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('role') }}</strong>
                                        </span>
                                    @endif
                                    </div>
                                </div>
                            @endif
                            <div class="pw-change-container">
                                <div class="form-group has-feedback row {{ $errors->has('password') ? ' has-error ' : '' }}">
                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                        {!! Form::label('password', trans('laravelusers::forms.create_user_label_password'), array('class' => 'col-md-3 control-label')); !!}
                                    @endif
                                    <div class="col-md-9">
                                        <div class="input-group">
                                            {!! Form::password('password', array('id' => 'password', 'class' => 'form-control ', 'placeholder' => trans('laravelusers::forms.create_user_ph_password'))) !!}
                                            <div class="input-group-append">
                                                <label class="input-group-text" for="password">
                                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                                        <i class="fa fa-fw {!! trans('laravelusers::forms.create_user_icon_password') !!}" aria-hidden="true"></i>
                                                    @else
                                                        {!! trans('laravelusers::forms.create_user_label_password') !!}
                                                    @endif
                                                </label>
                                            </div>
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
                                            <div class="input-group-append">
                                                <label class="input-group-text" for="password_confirmation">
                                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                                        <i class="fa fa-fw {!! trans('laravelusers::forms.create_user_icon_pw_confirmation') !!}" aria-hidden="true"></i>
                                                    @else
                                                        {!! trans('laravelusers::forms.create_user_label_pw_confirmation') !!}
                                                    @endif
                                                </label>
                                            </div>
                                        </div>
                                        @if ($errors->has('password_confirmation'))
                                            <span class="help-block">
                                                <strong>{{ $errors->first('password_confirmation') }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 col-sm-6 mb-2">
                                    <a href="#" class="btn btn-outline-secondary btn-block btn-change-pw mt-3" title="{!! trans('laravelusers::forms.change-pw') !!}">
                                        <i class="fa fa-fw fa-lock" aria-hidden="true"></i>
                                        <span></span> {!! trans('laravelusers::forms.change-pw') !!}
                                    </a>
                                </div>
                                <div class="col-12 col-sm-6">
                                    {!! Form::button(trans('laravelusers::forms.save-changes'), array('class' => 'btn btn-success btn-block margin-bottom-1 mt-3 mb-2 btn-save','type' => 'button', 'data-toggle' => 'modal', 'data-target' => '#confirmSave', 'data-title' => trans('laravelusers::modals.edit_user__modal_text_confirm_title'), 'data-message' => trans('laravelusers::modals.edit_user__modal_text_confirm_message'))) !!}
                                </div>
                            </div>
                        {!! Form::close() !!}
                    </div>
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
    @if(config('laravelusers.tooltipsEnabled'))
        @include('laravelusers::scripts.tooltips')
    @endif
@endsection

