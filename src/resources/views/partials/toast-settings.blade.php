@php($toastOptions = \jeremykenedy\laravelusers\Support\ToastSettings::values())
<div class="lu-toast-settings" data-lu-toast-settings @if(!in_array(old('notifications_driver', config('laravelusers.notifications.driver', 'alert')), ['toast', 'both'], true)) hidden @endif>
    <h3 class="lu-title-heading"><span class="lu-title-icon">@include('laravelusers::partials.icon', ['name' => 'settings'])</span><span>{{ __('laravelusers::ui.toast_settings') }}</span></h3>
    <p class="lu-muted text-muted">{{ __('laravelusers::ui.toast_settings_hint') }}</p>
    <div class="lu-toast-options-grid">
        @foreach(\jeremykenedy\laravelusers\Support\ToastSettings::fields() as $key => $field)
            @if($field['type'] !== 'checkbox')
                <div>
                    <label for="settings-toast-{{ $key }}">{{ __('laravelusers::ui.toast_'.$key) }}</label>
                    @if($field['type'] === 'select')
                        <select id="settings-toast-{{ $key }}" name="toast[{{ $key }}]" class="{{ $modern ? 'lu-input' : 'form-control' }}">@foreach($field['options'] as $option)<option value="{{ $option }}" @if(old('toast.'.$key, $toastOptions[$key]) === $option) selected @endif>{{ ucwords(str_replace('-', ' ', $option)) }}</option>@endforeach</select>
                    @else
                        <input id="settings-toast-{{ $key }}" name="toast[{{ $key }}]" type="number" class="{{ $modern ? 'lu-input' : 'form-control' }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}" value="{{ old('toast.'.$key, $toastOptions[$key]) }}">
                    @endif
                </div>
            @endif
        @endforeach
    </div>
    <div class="lu-toast-checks-grid">
        @foreach(\jeremykenedy\laravelusers\Support\ToastSettings::fields() as $key => $field)
            @if($field['type'] === 'checkbox')
                <label class="lu-settings-check"><input type="hidden" name="toast[{{ $key }}]" value="0"><input type="checkbox" name="toast[{{ $key }}]" value="1" @if(old('toast.'.$key, $toastOptions[$key])) checked @endif><span>{{ __('laravelusers::ui.toast_'.$key) }}</span></label>
            @endif
        @endforeach
    </div>
</div>
