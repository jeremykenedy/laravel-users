@extends(config('laravelusers.laravelUsersBladeExtended') === 'laravelusers::layouts.app' ? 'laravelusers::modern.layout' : config('laravelusers.laravelUsersBladeExtended'))

@section('template_linked_css')
    @include('laravelusers::modern.styles')
    @include('laravelusers::partials.avatar-styles')
    @include('laravelusers::partials.table-styles')
@endsection

@section('content')
    @php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
    <main id="laravelusers" data-lu-theme="{{ \jeremykenedy\laravelusers\Support\Frontend::theme() }}" data-lu-responsive-table="{{ config('laravelusers.responsiveTable', false) ? 'true' : 'false' }}" data-lu-responsive-buttons="{{ config('laravelusers.responsiveButtons', true) && config('laravelusers.iconsEnabled', true) ? 'true' : 'false' }}" data-lu-full-width="{{ config('laravelusers.fullWidth', false) ? 'true' : 'false' }}" class="lu-shell {{ $tailwind ? 'lu:mx-auto lu:max-w-6xl lu:px-6 lu:py-8' : 'container py-4' }}">
        @if(config('laravelusers.showHeader', true))
        @if(config('laravelusers.headerView'))
            @include(config('laravelusers.headerView'))
        @else
        <nav class="lu-toolbar" aria-label="{{ __('laravelusers::ui.navigation') }}">
            <a class="lu-brand" href="{{ route('users') }}">{{ config('app.name', 'Laravel') }} / {{ __('laravelusers::app.nav.users') }}</a>
            <div class="lu-actions">
                @auth
                    <span>{{ Auth::user()->name }}</span>
                    @if(config('laravelusers.showLogout', true) && Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="lu-button lu-secondary" type="submit">@include('laravelusers::partials.icon', ['name' => 'logout']) {{ __('laravelusers::ui.logout') }}</button></form>
                    @endif
                @endauth
                @if(config('laravelusers.themeToggle'))
                    @include('laravelusers::partials.theme-toggle')
                @endif
            </div>
        </nav>
        @endif
        @endif
        @if(config('laravelusers.enablePackageBootstapAlerts'))
            @foreach(['success', 'error'] as $status)
                @if(session($status))
                    <div class="lu-alert" role="status">{{ session($status) }}</div>
                @endif
            @endforeach
        @endif
        @if($errors->any())
            <div class="lu-alert lu-error" role="alert">
                <p>{{ __('laravelusers::ui.validation') }}</p>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('users_content')
        @include('laravelusers::modern.confirmation')
        @if(config('laravelusers.footerView'))
            @include(config('laravelusers.footerView'))
        @endif
    </main>
@endsection

@section('template_scripts')
    @include('laravelusers::scripts.theme')
    @include('laravelusers::modern.scripts')
    @include('laravelusers::scripts.table-controls')
    @include('laravelusers::scripts.welcome-options')
    @include('laravelusers::scripts.avatars')
    @include('laravelusers::scripts.dates')
    @include('laravelusers::scripts.bulk-actions')
    @include('laravelusers::scripts.columns')
@endsection
