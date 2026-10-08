@extends(config('laravelusers.laravelUsersBladeExtended') === 'laravelusers::layouts.app' ? 'laravelusers::modern.layout' : config('laravelusers.laravelUsersBladeExtended'))

@section('template_linked_css')
    @include('laravelusers::modern.styles')
    @include('laravelusers::partials.avatar-styles')
    @include('laravelusers::partials.table-styles')
    @include('laravelusers::partials.profile-styles')
    @include('laravelusers::partials.email-styles')
    @include('laravelusers::partials.user-menu-styles')
    @include('laravelusers::partials.settings-styles')
    @include('laravelusers::partials.notification-styles')
@endsection

@section('content')
    @php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
    <main id="laravelusers" data-lu-table-buttons-icon-only="{{ config('laravelusers.tableButtonsIconOnly', false) && config('laravelusers.iconsEnabled', true) ? 'true' : 'false' }}" data-lu-theme="{{ \jeremykenedy\laravelusers\Support\Frontend::theme() }}" data-lu-responsive-table="{{ config('laravelusers.responsiveTable', false) ? 'true' : 'false' }}" data-lu-responsive-buttons="{{ config('laravelusers.responsiveButtons', false) && config('laravelusers.iconsEnabled', true) ? 'true' : 'false' }}" data-lu-full-width="{{ config('laravelusers.fullWidth', false) ? 'true' : 'false' }}" class="lu-shell {{ $tailwind ? 'lu:mx-auto lu:max-w-6xl lu:px-6 lu:py-8' : 'container py-4' }}">
        @if(config('laravelusers.showHeader', true))
        @if(config('laravelusers.headerView'))
            @include(config('laravelusers.headerView'))
        @else
        <nav class="lu-toolbar" aria-label="{{ __('laravelusers::ui.navigation') }}">
            <a class="lu-brand" href="{{ url('/') }}">{{ __('laravelusers::ui.package_name') }}</a>
            <div class="lu-actions">
                @include('laravelusers::partials.user-menu')
                @if(config('laravelusers.themeToggle'))
                    @include('laravelusers::partials.theme-toggle')
                @endif
            </div>
        </nav>
        @endif
        @endif
        @if(config('laravelusers.showBreadcrumbs', false))
            @include('laravelusers::partials.breadcrumbs')
        @endif
        @include('laravelusers::partials.notifications')
        @yield('users_content')
        @include('laravelusers::modern.confirmation')
        <x-laravelusers::email-modal />
        @if(config('laravelusers.footerView'))
            @include(config('laravelusers.footerView'))
        @endif
    </main>
@endsection

@section('template_scripts')
    @include('laravelusers::scripts.theme')
    @include('laravelusers::modern.scripts')
    @include('laravelusers::partials.icon-templates')
    @include('laravelusers::scripts.table-controls')
    @include('laravelusers::scripts.welcome-options')
    @include('laravelusers::scripts.avatars')
    @include('laravelusers::scripts.dates')
    @include('laravelusers::scripts.bulk-actions')
    @include('laravelusers::scripts.table-view')
    @include('laravelusers::scripts.columns')
    @include('laravelusers::scripts.activity-icons')
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
    @include('laravelusers::scripts.account-tabs')
    @include('laravelusers::scripts.edit-tabs')
@endsection
