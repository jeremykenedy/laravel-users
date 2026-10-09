<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- CSRF Token --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@if (trim($__env->yieldContent('template_title')))@yield('template_title') | @endif {{ config('app.name', 'Laravel') }}</title>

        {{-- Styles --}}
        @if(config('laravelusers.enableBootstrapCssCdn'))
            <link rel="stylesheet" type="text/css" href="{{ config('laravelusers.bootstrapCssCdn') }}">
        @endif
        @if(config('laravelusers.enableAppCss'))
            <link rel="stylesheet" type="text/css" href="{{ asset(config('laravelusers.appCssPublicFile')) }}">
        @endif

        @if(\jeremykenedy\laravelusers\Support\Frontend::theme() !== 'light' || config('laravelusers.themeToggle'))
            @include('laravelusers::partials.legacy-theme')
        @endif
        <style>
            #laravelusers .btn:hover, #laravelusers .btn:focus { text-decoration: none; }
            #laravelusers [data-lu-full-width="true"] .container { max-width: none; }
            #laravelusers[data-lu-responsive-buttons="false"] .users-table .btn span { display: inline !important; }
            #laravelusers .users-table .btn { white-space: nowrap; }
            #laravelusers .lu-sort { border: 0; background: none; color: inherit; font: inherit; cursor: pointer; }
            #laravelusers .lu-column-filter { width: 100%; min-width: 90px; border: 1px solid #ced4da; border-radius: 4px; padding: 4px; }
        </style>
        @include('laravelusers::partials.avatar-styles')
    @include('laravelusers::partials.table-styles')
        @include('laravelusers::partials.email-styles')
        @include('laravelusers::partials.user-menu-styles')
        @include('laravelusers::partials.settings-styles')
    @include('laravelusers::partials.notification-styles')
        @yield('template_linked_css')

        {{-- Scripts --}}
        <script>
            window.Laravel = {!! json_encode([
                'csrfToken' => csrf_token(),
            ]) !!};
        </script>
    </head>
    <body id="laravelusers" data-lu-table-buttons-icon-only="{{ config('laravelusers.tableButtonsIconOnly', false) && config('laravelusers.fontAwesomeEnabled', true) ? 'true' : 'false' }}" data-lu-responsive-table="{{ config('laravelusers.responsiveTable', false) ? 'true' : 'false' }}" data-lu-responsive-buttons="{{ config('laravelusers.responsiveButtons', false) && config('laravelusers.fontAwesomeEnabled', true) ? 'true' : 'false' }}" data-lu-theme="{{ \jeremykenedy\laravelusers\Support\Frontend::theme() }}">
        <div id="app" data-lu-full-width="{{ config('laravelusers.fullWidth', false) ? 'true' : 'false' }}">
            @if(config('laravelusers.showHeader', true))
            @if(config('laravelusers.headerView'))
                @include(config('laravelusers.headerView'))
            @else
            <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
                <div class="container">
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ __('laravelusers::ui.package_name') }}
                    </a>
                    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Side Of Navbar -->
                        <ul class="navbar-nav mr-auto">

                        </ul>


                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ml-auto">
                            <!-- Authentication Links -->
                            @guest
                                <li><a class="nav-link" href="{{ route('login') }}">Login</a></li>
                                <li><a class="nav-link" href="{{ route('register') }}">Register</a></li>
                            @else
                                <li><a class="nav-link" href="{{ route('users') }}">{!! trans('laravelusers::app.nav.users') !!}</a></li>
                                <li class="nav-item">@include('laravelusers::partials.user-menu')</li>
                            @endguest
                            @if(config('laravelusers.themeToggle'))<li class="nav-item">@include('laravelusers::partials.theme-toggle')</li>@endif
                        </ul>
                    </div>
                </div>
            </nav>
            @endif
            @endif

            @if(config('laravelusers.showBreadcrumbs', false))
                <div class="container">@include('laravelusers::partials.breadcrumbs')</div>
            @endif

            <main class="py-4 laravel-users-main-card">
                @yield('content')
                <x-laravelusers::email-modal />
                @if(config('laravelusers.footerView'))@include(config('laravelusers.footerView'))@endif
            </main>
        </div>

        {{-- Scripts --}}
        @if(config('laravelusers.enablejQueryCdn'))
            <script src="{{ asset(config('laravelusers.jQueryCdn')) }}"></script>
        @endif
        @if(config('laravelusers.enableBootstrapPopperJsCdn'))
            <script src="{{ asset(config('laravelusers.bootstrapPopperJsCdn')) }}"></script>
        @endif
        @if(config('laravelusers.enableBootstrapJsCdn'))
            <script src="{{ asset(config('laravelusers.bootstrapJsCdn')) }}"></script>
        @endif
        @if(config('laravelusers.enableAppJs'))
            <script src="{{ asset(config('laravelusers.appJsPublicFile')) }}"></script>
        @endif
        @include('laravelusers::scripts.toggleText')

        @if(\jeremykenedy\laravelusers\Support\Frontend::theme() !== 'light' || config('laravelusers.themeToggle'))
            @include('laravelusers::scripts.theme')
        @endif
        @yield('template_scripts')
        @include('laravelusers::partials.icon-templates')
    @include('laravelusers::scripts.table-controls')
    @include('laravelusers::scripts.welcome-options')
    @include('laravelusers::scripts.avatars')
    @include('laravelusers::scripts.dates')
    @include('laravelusers::scripts.bulk-actions')
    @include('laravelusers::scripts.table-view')
    @include('laravelusers::scripts.columns')
    @include('laravelusers::scripts.activity-icons')
    @include('laravelusers::scripts.table-buttons')
    @include('laravelusers::scripts.emails')
    @include('laravelusers::scripts.password-meter')
    @include('laravelusers::scripts.user-menu')
    @include('laravelusers::scripts.notifications')
    @include('laravelusers::scripts.user-appearance')
    @include('laravelusers::scripts.table-text')
    @include('laravelusers::scripts.packages')
        @include('laravelusers::scripts.cleanup-settings')
        @include('laravelusers::scripts.goodbye-options')
        @include('laravelusers::scripts.settings-tabs')
        @include('laravelusers::scripts.account')

    </body>
</html>
