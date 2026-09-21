<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('template_title') | {{ config('app.name', 'Laravel') }}</title>
    @if(config('laravelusers.enableAppCss'))
        <link rel="stylesheet" href="{{ asset(config('laravelusers.appCssPublicFile')) }}">
    @endif
    @yield('template_linked_css')
    <style>body { margin: 0; background: #f4f6fa; } body:has([data-lu-theme="dark"]) { background: #111827; }</style>
</head>
<body>
    @yield('content')
    @if(config('laravelusers.enableAppJs'))
        <script src="{{ asset(config('laravelusers.appJsPublicFile')) }}"></script>
    @endif
    @yield('template_scripts')
</body>
</html>
