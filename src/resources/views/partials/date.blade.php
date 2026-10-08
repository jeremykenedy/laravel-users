@if($value)
    <time class="lu-date" data-lu-time datetime="{{ \Illuminate\Support\Carbon::parse($value, 'UTC')->utc()->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($value, 'UTC')->utc()->format('M j, Y H:i') }} UTC</time>
@endif
