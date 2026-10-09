@if(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'bootstrap5' && config('laravelusers.enableBootstrapCssCdn'))
    <link rel="stylesheet" href="{{ config('laravelusers.bootstrap5CssCdn') }}">
@elseif(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
    @include('laravelusers::partials.asset', ['name' => 'tailwind.css'])
@endif
@include('laravelusers::partials.asset', ['name' => 'modern.css'])

@if(config('laravelusers.themeToggle'))
    @include('laravelusers::partials.theme-toggle-styles')
@endif
