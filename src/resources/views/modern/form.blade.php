<form class="lu-form lu-pad" @if(isset($user) && config('laravelusers.confirmSave', true)) data-lu-confirm="{{ __('laravelusers::modals.edit_user__modal_text_confirm_message') }}" data-lu-confirm-title="{{ __('laravelusers::modals.edit_user__modal_text_confirm_title') }}" @endif method="POST" action="{{ isset($user) ? route(($deletedUser ?? false) ? 'users.deleted.update' : 'users.update', $user->id) : route('users.store') }}">
    @csrf
    @if(isset($user))@method('PUT')@endif
    @php($editing = isset($user))
    @php($accountAccess = ($accountPreferenceAvailable ?? false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_account_access'))
    @if($editing)
        <nav class="lu-settings-tabs lu-edit-tabs" role="tablist" aria-label="{{ __('laravelusers::ui.edit_sections') }}">
            @foreach(['profile' => ['user', 'edit_profile'], 'password' => ['lock', 'edit_password']] as $section => [$icon, $label])
                <button type="button" role="tab" id="lu-edit-tab-{{ $section }}" aria-controls="lu-edit-{{ $section }}" aria-selected="{{ $section === 'profile' ? 'true' : 'false' }}" data-lu-edit-tab="{{ $section }}">@include('laravelusers::partials.icon', ['name' => $icon]) {{ __('laravelusers::ui.'.$label) }}</button>
            @endforeach
            @if(($avatarSourceEnabled ?? false) || ($appearanceEnabled ?? false))<button type="button" role="tab" id="lu-edit-tab-appearance" aria-controls="lu-edit-appearance" aria-selected="false" data-lu-edit-tab="appearance">@include('laravelusers::partials.icon', ['name' => 'settings']) {{ __('laravelusers::ui.settings_appearance') }}</button>@endif
            @if($rolesEnabled || ($permissionsEnabled ?? false))<button type="button" role="tab" id="lu-edit-tab-roles" aria-controls="lu-edit-roles" aria-selected="false" data-lu-edit-tab="roles">@include('laravelusers::partials.icon', ['name' => 'role']) {{ __('laravelusers::ui.edit_roles') }}</button>@endif
            @if($accountAccess)<button type="button" role="tab" id="lu-edit-tab-account" aria-controls="lu-edit-account" aria-selected="false" data-lu-edit-tab="account">@include('laravelusers::partials.icon', ['name' => 'user']) {{ __('laravelusers::ui.edit_account_access') }}</button>@endif
        </nav>
    @endif
    <div @if($editing) id="lu-edit-profile" role="tabpanel" aria-labelledby="lu-edit-tab-profile" data-lu-edit-panel="profile" @endif class="lu-form-grid">
        @include('laravelusers::partials.user-fields', ['fields' => ['name' => 'create_user_label_username', 'email' => 'create_user_label_email']])
    </div>
    <div @if($editing) id="lu-edit-password" role="tabpanel" aria-labelledby="lu-edit-tab-password" data-lu-edit-panel="password" @endif class="lu-form-grid">
        @include('laravelusers::partials.user-fields', ['fields' => ['password' => 'create_user_label_password', 'password_confirmation' => 'create_user_label_pw_confirmation']])
    </div>
    <div @if($editing) id="lu-edit-roles" role="tabpanel" aria-labelledby="lu-edit-tab-roles" data-lu-edit-panel="roles" @endif>
    @if($rolesEnabled)
        <div class="lu-field {{ \jeremykenedy\laravelusers\Support\Frontend::classes('field') }}">
            <label class="{{ \jeremykenedy\laravelusers\Support\Frontend::classes('field-label') }}" for="role">{{ __('laravelusers::forms.create_user_label_role') }}</label>
            <div class="lu-control {{ \jeremykenedy\laravelusers\Support\Frontend::classes('field-control') }}"><div class="lu-input-group {{ \jeremykenedy\laravelusers\Support\Frontend::classes('control') }}">
            <select class="lu-input {{ \jeremykenedy\laravelusers\Support\Frontend::classes('select') }}" id="role" name="{{ isset($user) ? 'role[]' : 'role' }}" @if(isset($user)) multiple @endif required @if($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                <option value="">{{ __('laravelusers::forms.create_user_ph_role') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ in_array((string) $role->id, array_map('strval', (array) old('role', $currentRole ?? [])), true) ? 'selected' : '' }}>{{ $role->name }}@if(config('laravelusers.showRoleLevels', true) && isset($role->getAttributes()['level'])) ({{ __('laravelusers::ui.role_level', ['level' => $role->getAttributes()['level']]) }})@endif</option>
                @endforeach
            </select>
            @if(config('laravelusers.iconsEnabled', true))<label class="lu-input-icon" for="role">@include('laravelusers::partials.icon', ['name' => 'role'])</label>@endif
            </div>
            @if($errors->has('role'))<p class="lu-field-error" id="role-error">{{ $errors->first('role') }}</p>@endif
            </div>
        </div>
    @endif
    @include('laravelusers::partials.user-permissions', ['modern' => true])
    </div>
    <div @if($editing) id="lu-edit-appearance" role="tabpanel" aria-labelledby="lu-edit-tab-appearance" data-lu-edit-panel="appearance" @endif>
        @include('laravelusers::partials.avatar-source', ['modern' => true])
        @include('laravelusers::partials.user-appearance', ['modern' => true])
    </div>
    <div @if($editing) id="lu-edit-account" role="tabpanel" aria-labelledby="lu-edit-tab-account" data-lu-edit-panel="account" @endif>
        @include('laravelusers::partials.account-access', ['modern' => true])
    </div>
    @if(!isset($user))@include('laravelusers::partials.welcome-options')@endif
    <div class="lu-actions lu-form-actions">
        <button class="lu-button lu-success" type="submit">@include('laravelusers::partials.icon', ['name' => isset($user) ? 'save' : 'add-user']) {{ isset($user) ? __('laravelusers::ui.save') : __('laravelusers::laravelusers.create-new-user') }}</button>
        <a class="lu-button lu-secondary" href="{{ route(($deletedUser ?? false) ? 'users.deleted' : 'users') }}">@include('laravelusers::partials.icon', ['name' => 'close']) {{ __('laravelusers::forms.cancel') }}</a>
    </div>
</form>
