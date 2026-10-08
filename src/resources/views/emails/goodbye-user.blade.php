@component('mail::message')
<x-laravelusers::email-copy :greeting="$greeting" :body="$body" :signoff="$signoff" />
@foreach($accountLinks as $action => $url)
@component('mail::button', ['url' => $url, 'color' => $action === 'force_delete' ? 'error' : 'primary'])
{{ __('laravelusers::ui.account_'.$action) }}
@endcomponent
@endforeach
@if($accountLinks && $showLinkExpiry)
@component('mail::panel')
{{ $linkExpiry ? __('laravelusers::ui.goodbye_link_expiry', ['date' => \Illuminate\Support\Carbon::parse($linkExpiry)->utc()->format(config('laravelusers.emails.date_format', 'M j, Y g:i A T'))]) : __('laravelusers::ui.account_never_expiry') }}
@endcomponent
@endif
@if($retentionUntil)
@component('mail::panel')
{{ __('laravelusers::ui.goodbye_retention_until', ['date' => \Illuminate\Support\Carbon::parse($retentionUntil)->utc()->format(config('laravelusers.emails.date_format', 'M j, Y g:i A T'))]) }}
@endcomponent
@endif
@endcomponent
