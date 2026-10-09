<form method="POST" action="{{ route('users.settings.update') }}" class="lu-settings-form" autocomplete="off" data-lu-settings-form @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_appearance')) data-lu-avatar-preview-url="{{ route('users.settings.avatar-preview') }}" data-lu-avatar-preview-error="{{ __('laravelusers::ui.appearance_avatar_preview_failed') }}" @endif>
    @csrf @method('PUT')
    @if(!$settingsAvailable)<p class="lu-alert" role="status">{{ __('laravelusers::ui.settings_migration_required') }}</p>@endif
    <fieldset @if(!$settingsAvailable) disabled @endif>
        <legend class="{{ $modern ? 'lu-sr-only' : 'sr-only' }}">{{ __('laravelusers::ui.settings') }}</legend>
        <div data-lu-settings-panel="appearance" id="lu-settings-appearance" role="tabpanel" aria-labelledby="lu-tab-appearance">
        <fieldset class="lu-settings-appearance" @if(!\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_appearance')) disabled @endif>
            <legend class="lu-title-heading"><span class="lu-title-icon lu-title-icon-appearance">@include('laravelusers::partials.icon', ['name' => 'appearance'])</span><span>{{ __('laravelusers::ui.settings_appearance') }}</span></legend>
            <div class="lu-settings-grid">
                <div class="lu-settings-choice">
                    <label class="lu-icon-label lu-control-title" for="settings-avatar"><span class="lu-title-icon lu-title-icon-avatar">@include('laravelusers::partials.icon', ['name' => 'user'])</span><span>{{ __('laravelusers::ui.avatar_source') }}</span></label>
                    <select id="settings-avatar" name="avatar_source" class="{{ $modern ? 'lu-input' : 'form-control' }}">@foreach(\jeremykenedy\laravelusers\Support\Avatar::SOURCES as $source)<option value="{{ $source }}" @if(old('avatar_source', config('laravelusers.avatar.source', 'initials')) === $source) selected @endif>{{ __('laravelusers::ui.avatar_source_'.$source) }}</option>@endforeach</select>
                    <p class="lu-muted text-muted">{{ __('laravelusers::ui.settings_avatar_hint') }}</p>
                </div>
                @foreach(['profile' => 'profileCard', 'edit' => 'editCard'] as $kind => $prefix)
                    @php($keys = ['color' => $prefix.'Color', 'gradient' => $prefix.'Gradient', 'strength' => $prefix.'GradientStrength', 'highlight' => $prefix.'GradientHighlightColor'])
                    @include('laravelusers::partials.settings-appearance', ['kind' => $kind, 'keys' => $keys, 'fallback' => $keys, 'colorLabel' => __('laravelusers::ui.settings_'.$kind.'_color')])
                @endforeach
            </div>
            <h2 class="lu-title-heading"><span class="lu-title-icon lu-title-icon-appearance">@include('laravelusers::partials.icon', ['name' => 'appearance'])</span><span>{{ __('laravelusers::ui.appearance_dark') }}</span></h2>
            <p class="lu-muted text-muted">{{ __('laravelusers::ui.appearance_dark_hint') }}</p>
            <div class="lu-settings-grid lu-settings-dark-grid">
                @foreach(['profile' => 'profileCard', 'edit' => 'editCard'] as $kind => $prefix)
                    @include('laravelusers::partials.settings-appearance', ['kind' => $kind.'_dark', 'keys' => ['color' => $prefix.'DarkColor', 'gradient' => $prefix.'DarkGradient', 'strength' => $prefix.'DarkGradientStrength', 'highlight' => $prefix.'DarkGradientHighlightColor'], 'fallback' => ['color' => $prefix.'Color', 'gradient' => $prefix.'Gradient', 'strength' => $prefix.'GradientStrength', 'highlight' => $prefix.'GradientHighlightColor'], 'colorLabel' => __('laravelusers::ui.settings_'.$kind.'_dark_color')])
                @endforeach
            </div>
            <div class="lu-settings-choice">
                <label class="lu-settings-check" for="settings-breadcrumbs"><input type="hidden" name="show_breadcrumbs" value="0"><input id="settings-breadcrumbs" type="checkbox" name="show_breadcrumbs" value="1" @if(old('show_breadcrumbs', config('laravelusers.showBreadcrumbs', false))) checked @endif>@include('laravelusers::partials.icon', ['name' => 'breadcrumbs']) {{ __('laravelusers::ui.settings_breadcrumbs') }}</label>
                <p class="lu-muted text-muted">{{ __('laravelusers::ui.settings_breadcrumbs_hint') }}</p>
            </div>
            <p class="lu-field-error text-danger" data-lu-avatar-preview-status role="status" hidden></p>
        </fieldset>
        </div>
        <div data-lu-settings-panel="notifications" id="lu-settings-notifications" role="tabpanel" aria-labelledby="lu-tab-notifications">
            @include('laravelusers::partials.settings-notifications')
        </div>
        <div data-lu-settings-panel="access" id="lu-settings-access" role="tabpanel" aria-labelledby="lu-tab-access">
        @if($accessAvailable)
            <h2 class="lu-title-heading"><span class="lu-title-icon">@include('laravelusers::partials.icon', ['name' => 'role'])</span><span>{{ __('laravelusers::ui.settings_access') }}</span></h2><p class="lu-muted text-muted">{{ __('laravelusers::ui.settings_access_hint') }}</p>
            @foreach(\jeremykenedy\laravelusers\Support\UserAccess::ACTIONS as $action)
                @php($rule = old('access.'.$action, config('laravelusers.access.'.$action, ['mode' => 'inherit'])))
                <fieldset class="lu-access-rule"><legend>{{ __('laravelusers::ui.access_'.$action) }}</legend>
                    <div><label for="access-{{ $action }}-mode">{{ __('laravelusers::ui.settings_access_mode') }}</label><select id="access-{{ $action }}-mode" name="access[{{ $action }}][mode]" class="{{ $modern ? 'lu-input' : 'form-control' }}">@foreach(['inherit', 'restricted', 'deny'] as $mode)<option value="{{ $mode }}" @if(($rule['mode'] ?? 'inherit') === $mode) selected @endif>{{ __('laravelusers::ui.settings_mode_'.$mode) }}</option>@endforeach</select></div>
                    <div><span>{{ __('laravelusers::ui.settings_roles') }}</span><div class="lu-access-choices">@forelse($roles as $role)<label><input type="checkbox" name="access[{{ $action }}][roles][]" value="{{ $role->getKey() }}" @if(in_array((string) $role->getKey(), array_map('strval', $rule['roles'] ?? []), true)) checked @endif> {{ $role->name }}</label>@empty<span class="lu-muted">{{ __('laravelusers::ui.settings_no_roles') }}</span>@endforelse</div></div>
                    <div><span>{{ __('laravelusers::ui.settings_permissions') }}</span><div class="lu-access-choices">@forelse($permissions as $permission)<label><input type="checkbox" name="access[{{ $action }}][permissions][]" value="{{ $permission->getKey() }}" @if(in_array((string) $permission->getKey(), array_map('strval', $rule['permissions'] ?? []), true)) checked @endif> {{ $permission->name }}</label>@empty<span class="lu-muted">{{ __('laravelusers::ui.settings_no_permissions') }}</span>@endforelse</div></div>
                    @if($levelsAvailable)<div><label for="access-{{ $action }}-level">{{ __('laravelusers::ui.settings_level') }}</label><input id="access-{{ $action }}-level" name="access[{{ $action }}][level]" type="number" min="1" max="100000" value="{{ $rule['level'] ?? '' }}" class="{{ $modern ? 'lu-input' : 'form-control' }}"></div>@endif
                </fieldset>
            @endforeach
        @else<p class="lu-muted text-muted">{{ __('laravelusers::ui.settings_roles_required') }}</p>@endif
        </div>
        <div class="lu-settings-footer"><button type="submit" class="{{ $modern ? 'lu-button' : 'btn btn-primary' }}">@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.settings_save') }}</button></div>
    </fieldset>
</form>
