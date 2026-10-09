@component('mail::message')
@if($greeting !== '')
<div style="white-space: pre-wrap; margin-bottom: 16px;">{{ $greeting }}</div>
@endif

<div style="white-space: pre-wrap;">{{ $body }}</div>

@foreach($accountLinks as $action => $url)
@component('mail::button', ['url' => $url, 'color' => $action === 'force_delete' ? 'error' : 'primary'])
{{ __('laravelusers::ui.account_'.$action) }}
@endcomponent
@endforeach

@if($accountLinks)
@component('mail::panel')
{{ $accountMinutes === 0 ? __('laravelusers::ui.account_never_expiry') : __('laravelusers::ui.account_links_expiry', ['minutes' => $accountMinutes]) }}
@endcomponent
@endif

@if($signoff !== '')
<div style="white-space: pre-wrap; margin-top: 24px;">{{ $signoff }}</div>
@endif
@endcomponent
