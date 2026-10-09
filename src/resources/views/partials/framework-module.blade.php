@if(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'material3')
    @include('laravelusers::partials.asset', ['name' => 'material3.js', 'module' => true])
@endif
