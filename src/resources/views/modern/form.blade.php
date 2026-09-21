<form class="lu-form lu-panel lu-pad" method="POST" action="{{ isset($user) ? route('users.update', $user->id) : route('users.store') }}">
    @csrf
    @if(isset($user))@method('PUT')@endif
    @foreach(['name' => 'create_user_label_username', 'email' => 'create_user_label_email', 'password' => 'create_user_label_password', 'password_confirmation' => 'create_user_label_pw_confirmation'] as $field => $label)
        <div class="lu-field">
            <label for="{{ $field }}">{{ __('laravelusers::forms.'.$label) }}</label>
            <input class="lu-input {{ $tailwind ? 'lu:block lu:w-full lu:rounded-lg' : 'form-control' }}" id="{{ $field }}" name="{{ $field }}"
                type="{{ str_contains($field, 'password') ? 'password' : ($field === 'email' ? 'email' : 'text') }}"
                @if(!str_contains($field, 'password')) value="{{ old($field, isset($user) ? $user->$field : '') }}" maxlength="255" required @else autocomplete="new-password" @if(!isset($user)) required @endif @endif
                @if($errors->has($field)) aria-invalid="true" aria-describedby="{{ $field }}-error" @elseif(isset($user) && $field === 'password') aria-describedby="password-help" @endif>
            @if(isset($user) && $field === 'password')<p id="password-help" class="lu-muted">{{ __('laravelusers::ui.password_hint') }}</p>@endif
            @if($errors->has($field))<p class="lu-field-error" id="{{ $field }}-error">{{ $errors->first($field) }}</p>@endif
        </div>
    @endforeach
    @if($rolesEnabled)
        <div class="lu-field">
            <label for="role">{{ __('laravelusers::forms.create_user_label_role') }}</label>
            <select class="lu-input {{ $tailwind ? 'lu:w-full' : 'form-select' }}" id="role" name="role" required @if($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                <option value="">{{ __('laravelusers::forms.create_user_ph_role') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ in_array((string) $role->id, array_map('strval', (array) old('role', $currentRole ?? [])), true) ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
            @if($errors->has('role'))<p class="lu-field-error" id="role-error">{{ $errors->first('role') }}</p>@endif
        </div>
    @endif
    <div class="lu-actions">
        <button class="lu-button" type="submit">{{ isset($user) ? __('laravelusers::ui.save') : __('laravelusers::laravelusers.create-new-user') }}</button>
        <a class="lu-button lu-secondary" href="{{ route('users') }}">{{ __('laravelusers::forms.cancel') }}</a>
    </div>
</form>
