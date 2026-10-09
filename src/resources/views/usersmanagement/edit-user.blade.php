@extends(config('laravelusers.laravelUsersBladeExtended'))

@section('template_title')
    {!! trans('laravelusers::laravelusers.editing-user', ['name' => e($user->name)]) !!}
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
                            <span class="lu-list-title">@include('laravelusers::partials.icon', ['name' => 'edit']) <span class="lu-title-text">{!! trans('laravelusers::laravelusers.editing-user', ['name' => e($user->name)]) !!}</span></span>
                            <div class="pull-right">
                                <a href="{{ route(($deletedUser ?? false) ? 'users.deleted' : 'users') }}" class="btn btn-light btn-sm float-right" data-toggle="tooltip" data-placement="top" title="{!! trans('laravelusers::laravelusers.tooltips.back-users') !!}">
                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                        <i class="fas fa-fw fa-reply" aria-hidden="true"></i>
                                    @endif
                                    {!! trans('laravelusers::laravelusers.buttons.back-to-users') !!}
                                </a>
                                @if(!($deletedUser ?? false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('view_users'))
                                <a href="{{ url('/users/' . $user->id) }}" class="btn btn-light btn-sm float-right" data-toggle="tooltip" data-placement="left" title="{!! trans('laravelusers::laravelusers.tooltips.back-user') !!}">
                                    @if(config('laravelusers.fontAwesomeEnabled'))
                                        <i class="fas fa-fw fa-reply" aria-hidden="true"></i>
                                    @endif
                                    {!! trans('laravelusers::laravelusers.buttons.back-to-user') !!}
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route(($deletedUser ?? false) ? 'users.deleted.update' : 'users.update', $user->id) }}" role="form" class="needs-validation">
                            @method('PUT')
                            @csrf
                            <div class="form-group has-feedback row {{ $errors->has('name') ? ' has-error ' : '' }}">
                                @if(config('laravelusers.fontAwesomeEnabled'))
                                    <label for="name" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_username') !!}</label>
                                @endif
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input type="text" name="name" id="name" class="form-control" placeholder="{{ trans('laravelusers::forms.create_user_ph_username') }}" value="{{ old('name', $user->name) }}">
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
                                    <label for="email" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_email') !!}</label>
                                @endif
                                <div class="col-md-9">
                                    <div class="input-group">
                                        <input type="email" name="email" id="email" class="form-control" placeholder="{{ trans('laravelusers::forms.create_user_ph_email') }}" value="{{ old('email', $user->email) }}">
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
                            @include('laravelusers::partials.legacy-role-select', ['multipleRoles' => true, 'selectedRoles' => $currentRole ?? []])
                            @include('laravelusers::partials.avatar-source', ['modern' => false])
                            @include('laravelusers::partials.user-appearance', ['modern' => false])
                            @include('laravelusers::partials.user-permissions', ['modern' => false])
    @include('laravelusers::partials.account-access', ['modern' => false])
                            <div class="pw-change-container">
                                @include('laravelusers::partials.legacy-password-fields')
                            </div>
                            <div class="row">
                                <div class="col-12 col-sm-6 mb-2">
                                    <a href="#" class="btn btn-outline-secondary btn-block btn-change-pw mt-3" title="{!! trans('laravelusers::forms.change-pw') !!}">
                                        <i class="fa fa-fw fa-lock" aria-hidden="true"></i>
                                        <span></span> {!! trans('laravelusers::forms.change-pw') !!}
                                    </a>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button type="{{ config('laravelusers.confirmSave', true) ? 'button' : 'submit' }}" class="btn btn-success btn-block margin-bottom-1 mt-3 mb-2 btn-save" @if(config('laravelusers.confirmSave', true)) data-toggle="modal" data-target="#confirmSave" @endif data-title="{{ trans('laravelusers::modals.edit_user__modal_text_confirm_title') }}" data-message="{{ trans('laravelusers::modals.edit_user__modal_text_confirm_message') }}">
                                        {!! trans('laravelusers::forms.save-changes') !!}
                                    </button>
                                </div>
                            </div>
                        </form>
                        <x-laravelusers::email-actions :user="$user" :deleted="$deletedUser ?? false" />
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
