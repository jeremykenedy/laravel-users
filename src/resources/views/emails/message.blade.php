@component('mail::message')
<x-laravelusers::email-copy :greeting="$greeting" :body="$body" :signoff="$signoff" />
@endcomponent
