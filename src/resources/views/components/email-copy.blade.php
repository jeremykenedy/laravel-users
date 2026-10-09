@props(['greeting' => '', 'body' => '', 'signoff' => ''])
@if($greeting !== '')<div style="white-space: pre-wrap; margin-bottom: 16px;">{{ $greeting }}</div>@endif
@if($body !== '')<div style="white-space: pre-wrap;">{{ $body }}</div>@endif
@if($signoff !== '')<div style="white-space: pre-wrap; margin-top: 24px;">{{ $signoff }}</div>@endif
