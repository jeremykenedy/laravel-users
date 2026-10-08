@props(['showLogout' => null, 'logoutRoute' => 'logout'])
<span {{ $attributes->merge(['class' => 'lu-user-menu-component']) }}>@include('laravelusers::partials.user-menu', ['showLogout' => $showLogout, 'logoutRoute' => $logoutRoute])</span>
@once
    @include('laravelusers::partials.user-menu-styles')
    @include('laravelusers::partials.avatar-styles')
@endonce
@once
@push('laravelusers-components-scripts')
    @include('laravelusers::scripts.user-menu')
    @include('laravelusers::scripts.avatars')
@endpush
@endonce
