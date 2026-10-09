@props(['activity' => [], 'labels' => []])
<dl {{ $attributes->merge(['class' => 'lu-details']) }}>
    @if(array_key_exists('online', $activity))
        <div><dt>{{ $labels['presence'] ?? __('laravelusers::ui.presence') }}</dt><dd>{{ ($activity['online'] ?? false) ? ($labels['online'] ?? __('laravelusers::ui.online')) : ($labels['offline'] ?? __('laravelusers::ui.offline')) }}</dd></div>
    @endif
    <div><dt>{{ $labels['last_login_at'] ?? __('laravelusers::ui.last_login_at') }}</dt><dd>@if($activity['last_login_at'] ?? null)<time datetime="{{ $activity['last_login_at'] }}">{{ $activity['last_login_label'] ?? $activity['last_login_at'] }}</time>@else{{ $labels['no_logins'] ?? __('laravelusers::ui.no_logins') }}@endif</dd></div>
    @foreach(['device', 'os', 'browser', 'ip_address'] as $field)
        @if($activity[$field] ?? null)<div><dt>{{ $labels[$field] ?? __('laravelusers::ui.'.$field) }}</dt><dd>{{ $activity[$field] }}</dd></div>@endif
    @endforeach
</dl>
