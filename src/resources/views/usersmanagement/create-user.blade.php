@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
    {!! trans('laravelusers::laravelusers.create-new-user') !!}
@endsection

@section('template_linked_css')
    @include('laravelusers::partials.legacy-form-styles')
@endsection

@section('content')
    <div class="container">
        @if(config('laravelusers.enablePackageBootstapAlerts'))
            <div class="row">
                <div class="col-12">
                    @include('laravelusers::partials.form-status')
                </div>
            </div>
        @endif
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header lu-page-heading">
                        <div class="lu-page-heading-content">
                            <span class="lu-list-title">@include('laravelusers::partials.icon', ['name' => 'add-user']) <span class="lu-title-text">{!! trans('laravelusers::laravelusers.create-new-user') !!}</span></span>
                            <div class="pull-right">
                                <a href="{{ route('users') }}" class="btn btn-light btn-sm float-right" data-toggle="tooltip" data-placement="left" title="{!! trans('laravelusers::laravelusers.tooltips.back-users') !!}">
                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                        <i class="fas fa-fw fa-reply" aria-hidden="true"></i>
                                    @endif
                                    {!! trans('laravelusers::laravelusers.buttons.back-to-users') !!}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('users.store') }}" role="form" class="needs-validation">
                            @csrf
                            <div class="form-group has-feedback row {{ $errors->has('email') ? ' has-error ' : '' }}">
                                @if(config('laravelusers.fontAwesomeEnabled'))
                                    <label for="email" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_email') !!}</label>
                                @endif
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input type="text" name="email" id="email" class="form-control" placeholder="{{ trans('laravelusers::forms.create_user_ph_email') }}" value="{{ old('email') }}">
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
                            <div class="form-group has-feedback row {{ $errors->has('name') ? ' has-error ' : '' }}">
                                @if(config('laravelusers.fontAwesomeEnabled'))
                                    <label for="name" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_username') !!}</label>
                                @endif
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input type="text" name="name" id="name" class="form-control" placeholder="{{ trans('laravelusers::forms.create_user_ph_username') }}" value="{{ old('name') }}">
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
                            @include('laravelusers::partials.legacy-role-select')
                            @include('laravelusers::partials.legacy-password-fields', ['creating' => true])
                            @include('laravelusers::partials.avatar-source', ['modern' => false])
                            @include('laravelusers::partials.user-appearance', ['modern' => false])
                            @include('laravelusers::partials.user-permissions', ['modern' => false])
    @include('laravelusers::partials.account-access', ['modern' => false])
                            <button type="submit" class="btn btn-success margin-bottom-1 mb-1 float-right">
                                {!! trans('laravelusers::forms.create_user_button_text') !!}
                            </button>
                            @include('laravelusers::partials.welcome-options')
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('template_scripts')
    @if(config('laravelusers.tooltipsEnabled'))
        @include('laravelusers::scripts.tooltips')
    @endif
@endsection
