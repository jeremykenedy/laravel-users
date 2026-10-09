@if($rolesEnabled)
    <div class="form-group has-feedback row {{ $errors->has('role') ? ' has-error ' : '' }}">
        @if(config('laravelusers.fontAwesomeEnabled'))
            <label for="role" class="col-md-3 control-label">{!! trans('laravelusers::forms.create_user_label_role') !!}</label>
        @endif
        <div class="col-md-9">
            <div class="input-group">
                <select class="custom-select form-control" name="{{ ($multipleRoles ?? false) ? 'role[]' : 'role' }}" id="role" @if($multipleRoles ?? false) multiple @endif>
                    <option value="">{!! trans('laravelusers::forms.create_user_ph_role') !!}</option>
                    @if ($roles)
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @if(in_array($role->id, $selectedRoles ?? [])) selected="selected" @endif>{{ $role->name }}@if(config('laravelusers.showRoleLevels', true) && isset($role->getAttributes()['level'])) ({{ __('laravelusers::ui.role_level', ['level' => $role->getAttributes()['level']]) }})@endif</option>
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
