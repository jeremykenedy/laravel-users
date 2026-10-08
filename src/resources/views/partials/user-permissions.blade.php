@if($permissionsEnabled ?? false)
    <div class="{{ $modern ? 'lu-field' : 'form-group row' }}">
        <label for="permissions" class="{{ $modern ? '' : 'col-md-3 control-label' }}">{{ __('laravelusers::ui.direct_permissions') }}</label>
        <div class="{{ $modern ? 'lu-control' : 'col-md-9' }}">
            <input type="hidden" name="permissions_present" value="1">
            <div class="{{ $modern ? 'lu-input-group' : 'input-group' }}">
                <select id="permissions" name="permissions[]" class="{{ $modern ? 'lu-input form-select' : 'form-control custom-select' }}" multiple @if($errors->has('permissions')) aria-invalid="true" aria-describedby="permissions-error" @endif>
                    @foreach($permissions as $permission)<option value="{{ $permission->getKey() }}" @if(in_array((string) $permission->getKey(), array_map('strval', (array) old('permissions', old('permissions_present') ? [] : ($currentPermissions ?? []))), true)) selected @endif>{{ $permission->name }}</option>@endforeach
                </select>
                @if($modern && config('laravelusers.iconsEnabled', true))<label class="lu-input-icon" for="permissions">@include('laravelusers::partials.icon', ['name' => 'role'])</label>@elseif(!$modern && config('laravelusers.fontAwesomeEnabled', true))<div class="input-group-append"><label class="input-group-text mb-0" for="permissions"><i class="fa fa-fw fa-key" aria-hidden="true"></i></label></div>@endif
            </div>
            <p class="lu-muted text-muted">{{ __('laravelusers::ui.direct_permissions_hint') }}</p>
            @if($errors->has('permissions'))<p id="permissions-error" class="lu-field-error text-danger">{{ $errors->first('permissions') }}</p>@endif
        </div>
    </div>
@endif
