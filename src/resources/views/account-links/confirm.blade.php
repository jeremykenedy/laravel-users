<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} | {{ __('laravelusers::ui.account_link_title') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { margin: 0; background: #f3f6fa; color: #253145; font: 16px/1.6 system-ui, sans-serif; }
        main { box-sizing: border-box; width: min(100% - 32px, 480px); margin: 12vh auto; padding: 32px; background: #fff; border: 1px solid #dce3ed; border-radius: 12px; }
        h1 { margin: 0 0 16px; font-size: 24px; line-height: 1.3; }
        button { padding: 12px 20px; border: 0; border-radius: 6px; background: #2456c2; color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        button.danger { background: #b42332; }
        button:disabled { cursor: not-allowed; opacity: .65; }
        button:focus-visible, a:focus-visible { outline: 3px solid #769cfa; outline-offset: 4px; }
        a { color: #2456c2; }
        @media (prefers-color-scheme: dark) { body { background: #141d2b; color: #edf2fa; } main { background: #253145; border-color: #42516a; } a { color: #94b9ff; } }
    </style>
    @if(\jeremykenedy\laravelusers\Support\Frontend::stylesheet())
        @include('laravelusers::modern.styles')
        @include('laravelusers::modern.framework-styles')
    @endif
</head>
<body>
<main @if(\jeremykenedy\laravelusers\Support\Frontend::stylesheet()) id="laravelusers" class="lu-public-page" data-lu-css="{{ \jeremykenedy\laravelusers\Support\Frontend::framework() }}" data-lu-theme="{{ \jeremykenedy\laravelusers\Support\Frontend::theme() }}" @endif>
    @if($completed ?? false)
        <h1>{{ __('laravelusers::ui.account_'.$completed.'_complete') }}</h1>
        <p>{{ __('laravelusers::ui.account_'.$completed.'_done') }}</p>
        @if($completed === 'restore' && Route::has('login'))<a href="{{ route('login') }}">{{ __('laravelusers::ui.account_sign_in') }}</a>@endif
    @elseif($link ?? false)
        <h1>{{ __('laravelusers::ui.account_'.$link->action) }}</h1>
        <p>{{ __('laravelusers::ui.account_'.$link->action.'_confirm') }}</p>
        <p>{{ __('laravelusers::ui.account_link_once') }}</p>
        <form method="POST" action="{{ route('users.account-link.confirm', ['token' => $token]) }}">
            @csrf
            <button type="submit" @if(\jeremykenedy\laravelusers\Support\Frontend::stylesheet()) class="lu-button{{ $link->action === 'force_delete' ? ' lu-danger' : '' }}" @elseif($link->action === 'force_delete') class="danger" @endif>{{ __('laravelusers::ui.account_'.$link->action) }}</button>
        </form>
    @else
        <h1>{{ __('laravelusers::ui.account_link_invalid') }}</h1>
        <p>{{ __('laravelusers::ui.account_link_invalid_help') }}</p>
    @endif
</main>
@if(\jeremykenedy\laravelusers\Support\Frontend::stylesheet())
    @include('laravelusers::scripts.theme')
    @include('laravelusers::partials.framework-module')
@endif
</body>
</html>
