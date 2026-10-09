@if(!empty($userToasts))
    @include('laravelusers::partials.toast-animations')
    @foreach(collect($userToasts)->groupBy('position') as $position => $toasts)
        <div class="lu-toast-stack" data-lu-toast-position="{{ $position }}" aria-live="polite">
            @foreach($toasts as $toast)
                <div class="lu-toast" data-laravel-toast="blade" data-lu-toast data-lu-toast-id="{{ $toast['id'] }}" data-type="{{ $toast['type'] }}" data-auto-dismiss="{{ $toast['auto_dismiss'] ? 'true' : 'false' }}" data-duration="{{ $toast['duration'] }}" data-pause-on-hover="{{ $toast['pause_on_hover'] ? 'true' : 'false' }}" data-enter-animation="{{ $toast['enter_animation'] }}" data-enter-duration="{{ $toast['enter_duration'] }}" data-exit-animation="{{ $toast['exit_animation'] }}" data-exit-duration="{{ $toast['exit_duration'] }}" data-border="{{ $toast['show_border'] ? 'true' : 'false' }}" dir="{{ $toast['dir'] }}" role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}" style="opacity: {{ max(0, min(1, (float) $toast['opacity'])) }}">
                    @if($toast['show_progress'] && $toast['auto_dismiss'] && $toast['duration'] > 0)<div class="lu-toast-progress" data-position="{{ $toast['progress_position'] }}" data-direction="{{ $toast['progress_direction'] }}"><span data-lu-toast-progress></span></div>@endif
                    <div class="lu-toast-body">
                        @if($toast['show_icon'])<span class="lu-toast-icon">@include('laravelusers::partials.icon', ['name' => match($toast['type']) { 'success' => 'check', 'error', 'warning' => 'warning', default => 'notifications' }])</span>@endif
                        <div class="lu-toast-message">@if($toast['title'])<strong>{{ $toast['title'] }}</strong>@endif<span>{{ $toast['message'] }}</span></div>
                        @if($toast['show_close'])<button type="button" data-lu-dismiss-toast aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
@endif
