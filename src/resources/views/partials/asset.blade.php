@php($assetUrl = \jeremykenedy\laravelusers\Support\PublicAssets::url($name))
@if(str_ends_with($name, '.css'))
    @if($assetUrl)
        <link rel="stylesheet" href="{{ $assetUrl }}">
    @else
        <style>{!! \jeremykenedy\laravelusers\Support\PublicAssets::contents($name) !!}</style>
    @endif
@else
    @if($assetUrl)
        <script src="{{ $assetUrl }}"></script>
    @else
        <script>{!! \jeremykenedy\laravelusers\Support\PublicAssets::contents($name) !!}</script>
    @endif
@endif
