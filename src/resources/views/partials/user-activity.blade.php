@if(config('laravelusers.activity.online', false))
    <div class="lu-detail">
        <dt>{{ __('laravelusers::ui.presence') }}</dt>
        <dd>{{ __('laravelusers::ui.'.($userOnline === null ? 'unknown' : ($userOnline ? 'online' : 'offline'))) }}</dd>
    </div>
@endif
@if(config('laravelusers.activity.login', false))
    @foreach(['last_login_at', 'ip_address', 'device', 'os', 'browser'] as $field)
        <div class="lu-detail">
            <dt>{{ __('laravelusers::ui.'.$field) }}</dt>
            <dd>{{ $lastLogin->$field ?? __('laravelusers::ui.not_recorded') }}</dd>
        </div>
    @endforeach
@endif
