@extends(config('laravelusers.laravelUsersBladeExtended') === 'laravelusers::layouts.app' ? 'laravelusers::runtime.layout' : config('laravelusers.laravelUsersBladeExtended'))
@section('template_title', $nativePage['title'])
@section('template_linked_css')
    @include('laravelusers::modern.styles')
    @include('laravelusers::partials.avatar-styles')
    @include('laravelusers::partials.table-styles')
    @include('laravelusers::partials.profile-styles')
    @include('laravelusers::partials.email-styles')
    @include('laravelusers::partials.settings-styles')
    @include('laravelusers::partials.notification-styles')
    @if($nativeRuntime === 'livewire')@livewireStyles @endif
@endsection
@section('content')
    <main id="laravelusers" data-lu-css="{{ $nativePage['framework'] }}" data-lu-theme="{{ $nativePage['theme'] }}" data-lu-runtime="{{ $nativeRuntime }}" data-lu-full-width="{{ $nativePage['features']['full_width'] ? 'true' : 'false' }}" class="lu-shell laravel-users-main-card {{ \jeremykenedy\laravelusers\Support\Frontend::classes('shell') }}">
        @if(config('laravelusers.showHeader', true) && config('laravelusers.headerView'))@include(config('laravelusers.headerView'))@endif
        @if($nativeRuntime === 'livewire')
            @livewire('laravelusers.users-screen', ['nativePage' => $nativePage])
        @else
            <div id="lu-native-app"></div>
            <script type="application/json" id="lu-native-page">{!! json_encode($nativePage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
            <noscript><p role="status">{{ __('laravelusers::ui.navigation') }}: @foreach($nativePage['data']['navigation'] as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a> @endforeach</p></noscript>
        @endif
        @if(config('laravelusers.footerView'))@include(config('laravelusers.footerView'))@endif
    </main>
@endsection
@section('template_scripts')
    @if($nativeRuntime === 'livewire')@livewireScripts @endif
    @include('laravelusers::partials.asset', ['name' => 'runtime-'.$nativeRuntime.'.js'])
@endsection
