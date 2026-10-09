<div class="lu-notifications">
@if(config('laravelusers.enablePackageBootstapAlerts', true))
    @if(\jeremykenedy\laravelusers\Support\UserNotifications::useToast())
        @once @include('laravelusers::partials.toasts') @endonce
    @endif
    @if(\jeremykenedy\laravelusers\Support\UserNotifications::useAlerts())
        @foreach(['message' => 'info', 'success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $status => $type)
            @if(session($status))
                <div class="lu-flash alert alert-{{ $type }}" role="{{ $status === 'error' ? 'alert' : 'status' }}"><span>{{ session($status) }}</span>@if(config('laravelusers.notifications.dismissible', true))<button type="button" disabled data-lu-dismiss-alert aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button>@endif</div>
            @endif
        @endforeach
    @endif
@endif
@if($errors->any())
    <div class="lu-flash lu-error alert alert-danger" role="alert"><div><p>{{ __('laravelusers::ui.validation') }}</p><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@if(config('laravelusers.notifications.dismissible', true))<button type="button" disabled data-lu-dismiss-alert aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button>@endif</div>
@endif
</div>
