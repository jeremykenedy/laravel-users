<!DOCTYPE html>
<html lang="{{ config('app.locale', 'en') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('template_title') | {{ config('app.name', 'Laravel') }}</title>
    @if(config('laravelusers.enableAppCss'))<link rel="stylesheet" href="{{ asset(config('laravelusers.appCssPublicFile')) }}">@endif
    @yield('template_linked_css')
</head>
<body>
    @yield('content')
    @if(config('laravelusers.enableAppJs'))<script src="{{ asset(config('laravelusers.appJsPublicFile')) }}"></script>@endif
    @yield('template_scripts')
</body>
</html>
