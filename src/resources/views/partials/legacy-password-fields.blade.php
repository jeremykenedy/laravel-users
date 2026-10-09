<div class="form-group has-feedback row {{ $errors->has('password') ? ' has-error ' : '' }}">
    @if(config('laravelusers.fontAwesomeEnabled'))
        <label for="password" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_password') !!}</label>
    @endif
    <div class="col-md-9">
        <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" placeholder="{{ trans('laravelusers::forms.create_user_ph_password') }}">
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
        @include('laravelusers::partials.password-meter', ['creating' => $creating ?? false])
        @if ($errors->has('password'))
            <span class="help-block">
                <strong>{{ $errors->first('password') }}</strong>
            </span>
        @endif
    </div>
</div>
<div class="form-group has-feedback row {{ $errors->has('password_confirmation') ? ' has-error ' : '' }}">
    @if(config('laravelusers.fontAwesomeEnabled'))
        <label for="password_confirmation" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_pw_confirmation') !!}</label>
    @endif
    <div class="col-md-9">
        <div class="input-group">
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="{{ trans('laravelusers::forms.create_user_ph_pw_confirmation') }}">
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
        @include('laravelusers::partials.password-confirmation')
        @if ($errors->has('password_confirmation'))
            <span class="help-block">
                <strong>{{ $errors->first('password_confirmation') }}</strong>
            </span>
        @endif
    </div>
</div>
