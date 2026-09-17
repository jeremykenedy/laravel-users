@extends(config('laravelusers.laravelUsersBladeExtended') === 'laravelusers::layouts.app' ? 'laravelusers::modern.layout' : config('laravelusers.laravelUsersBladeExtended'))

@section('template_linked_css')
    @include('laravelusers::modern.styles')
@endsection

@section('content')
    @php($tailwind = \jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
    <main id="laravelusers" data-lu-theme="{{ \jeremykenedy\laravelusers\Support\Frontend::theme() }}" class="lu-shell {{ $tailwind ? 'lu:mx-auto lu:max-w-6xl lu:px-6 lu:py-8' : 'container py-4' }}">
        <nav class="lu-toolbar" aria-label="{{ __('laravelusers::ui.navigation') }}">
            <a class="lu-brand" href="{{ route('users') }}">{{ config('app.name', 'Laravel') }} / {{ __('laravelusers::app.nav.users') }}</a>
            <div class="lu-actions">
                @if(config('laravelusers.themeToggle'))
                    @include('laravelusers::partials.theme-toggle')
                @endif
                @auth
                    <span>{{ Auth::user()->name }}</span>
                    @if(Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="lu-button lu-secondary" type="submit">{{ __('laravelusers::ui.logout') }}</button></form>
                    @endif
                @endauth
            </div>
        </nav>
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
    </main>
@endsection

@section('template_scripts')
    @include('laravelusers::scripts.theme')
    @include('laravelusers::modern.scripts')
@endsection
