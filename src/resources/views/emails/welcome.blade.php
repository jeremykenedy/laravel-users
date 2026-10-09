@component('mail::message')
@if($copy)
<x-laravelusers::email-copy :greeting="$copy['greeting']" :body="$copy['body']" />
@else
# {{ __('laravelusers::ui.welcome_greeting', ['name' => $name]) }}

{{ $welcomeMessage }}
@endif

@if($resetUrl)
{{ __('laravelusers::ui.reset_notice') }}

@component('mail::button', ['url' => $resetUrl])
{{ __('laravelusers::ui.set_password') }}
@endcomponent

{{ __('laravelusers::ui.reset_expiry', ['minutes' => $resetMinutes]) }}
@elseif($loginUrl)
@component('mail::button', ['url' => $loginUrl])
{{ __('laravelusers::ui.sign_in') }}
@endcomponent
@endif

@if($copy)
<x-laravelusers::email-copy :signoff="$copy['signoff']" />
@else
{{ $appName }}
@endif
@endcomponent
