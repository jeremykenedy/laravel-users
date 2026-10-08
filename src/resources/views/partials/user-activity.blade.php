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
            <dd>@if($field === 'last_login_at')@include('laravelusers::partials.date', ['value' => $lastLogin->$field ?? null])@else{{ $lastLogin->$field ?? '' }}@endif</dd>
        </div>
    @endforeach
@endif
