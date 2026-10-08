@php($impersonation = session('laravelusers.impersonation'))
@if(is_array($impersonation))
    <div class="lu-impersonation-banner" role="status">
        <span class="lu-impersonation-mark">@include('laravelusers::partials.icon', ['name' => 'secret-agent'])</span>
        <span class="lu-impersonation-label">{{ __('laravelusers::ui.impersonation_banner', ['user' => Auth::user()->name]) }}</span>
        <form method="POST" action="{{ route('users.impersonation.stop') }}">
            @csrf
            <button type="submit" class="lu-impersonation-exit">@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.impersonation_exit') }}</button>
        </form>
    </div>
@endif
