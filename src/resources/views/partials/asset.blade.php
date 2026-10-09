@php($assetUrl = \jeremykenedy\laravelusers\Support\PublicAssets::url($name))
@if(str_ends_with($name, '.css'))
    @if($assetUrl)
        <link rel="stylesheet" href="{{ $assetUrl }}">
    @else
        <style>{!! \jeremykenedy\laravelusers\Support\PublicAssets::contents($name) !!}</style>
    @endif
@else
    @if($assetUrl)
        <script @if($module ?? false) type="module" data-navigate-once @elseif($once ?? false) data-navigate-once @endif src="{{ $assetUrl }}"></script>
    @else
        <script @if($module ?? false) type="module" data-navigate-once @elseif($once ?? false) data-navigate-once @endif>{!! \jeremykenedy\laravelusers\Support\PublicAssets::contents($name) !!}</script>
    @endif
@endif
