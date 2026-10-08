@component('mail::message')
<x-laravelusers::email-copy :greeting="$greeting" :body="$body" />

@component('mail::button', ['url' => $url])
{{ __('laravelusers::ui.reset_password') }}
@endcomponent

{{ $minutes === 0 ? __('laravelusers::ui.reset_never_expiry') : __('laravelusers::ui.reset_expiry', ['minutes' => $minutes]) }}

<x-laravelusers::email-copy :signoff="$signoff" />
@endcomponent
