@props(['id' => 'laravelusers-theme-toggle', 'target' => 'html'])
<span {{ $attributes->merge(['class' => 'lu-theme-component']) }}>@include('laravelusers::partials.theme-toggle', ['toggleId' => $id, 'themeTarget' => $target])</span>
@once
    @include('laravelusers::partials.theme-toggle-styles')
    @include('laravelusers::scripts.theme')
@endonce
