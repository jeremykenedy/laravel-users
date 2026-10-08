@if(config('laravelusers.activity.online', false))
    <div class="lu-detail">
        <dt>@include('laravelusers::partials.icon', ['name' => 'user']) {{ __('laravelusers::ui.presence') }}</dt>
        <dd>{{ __('laravelusers::ui.'.($userOnline === null ? 'unknown' : ($userOnline ? 'online' : 'offline'))) }}</dd>
    </div>
@endif
@if(config('laravelusers.activity.login', false))
    @foreach(['last_login_at', 'ip_address', 'device', 'os', 'browser'] as $field)
        <div class="lu-detail">
            <dt @if($field !== 'last_login_at') data-lu-activity-icon="{{ $field }}" @endif>@include('laravelusers::partials.icon', ['name' => ['last_login_at' => 'clock', 'ip_address' => 'network', 'device' => 'device', 'os' => 'device', 'browser' => 'browser'][$field]]) {{ __('laravelusers::ui.'.$field) }}</dt>
            <dd>@if($field === 'last_login_at')@include('laravelusers::partials.date', ['value' => $lastLogin->$field ?? null, 'empty' => __('laravelusers::ui.no_logins')])@elseif($field === 'ip_address' && !empty($lastLogin->$field))@include('laravelusers::partials.ip-address', ['ip' => $lastLogin->$field])@else{{ $lastLogin->$field ?? '' }}@endif</dd>
        </div>
    @endforeach
@endif
