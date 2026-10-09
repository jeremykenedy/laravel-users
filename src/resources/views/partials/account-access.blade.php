@if(($accountPreferenceAvailable ?? false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_account_access'))
<fieldset class="lu-user-appearance"><legend>@include('laravelusers::partials.icon', ['name' => 'settings']) {{ __('laravelusers::ui.account_access_title') }}</legend><p class="lu-muted text-muted">{{ __('laravelusers::ui.account_access_hint') }}</p>
@foreach(['enabled', 'settings_enabled'] as $setting)
<div class="{{ $modern ? 'lu-field' : 'form-group row' }}"><label class="{{ $modern ? '' : 'col-md-3 control-label' }}" for="account-{{ $setting }}">{{ __('laravelusers::ui.account_'.$setting) }}</label><div class="{{ $modern ? 'lu-control' : 'col-md-9' }}"><select class="{{ $modern ? 'lu-input' : 'form-control' }}" id="account-{{ $setting }}" name="account_{{ $setting }}">@php($current = $accountPreference?->$setting)@foreach(['inherit', 'on', 'off'] as $value)<option value="{{ $value }}" @if(old('account_'.$setting, $current === null ? 'inherit' : ($current ? 'on' : 'off')) === $value) selected @endif>{{ __('laravelusers::ui.'.($value === 'inherit' ? 'appearance_inherit' : 'account_'.$value)) }}</option>@endforeach</select>@error('account_'.$setting)<p class="lu-field-error text-danger">{{ $message }}</p>@enderror</div></div>
@endforeach
</fieldset>
@endif
