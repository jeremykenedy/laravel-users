@if($frameworkStylesheet = \jeremykenedy\laravelusers\Support\Frontend::stylesheet())
    @include('laravelusers::partials.asset', ['name' => $frameworkStylesheet])
@endif
