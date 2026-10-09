@if(class_exists(\Jeremykenedy\LaravelToast\Support\ToastAnimations::class))
    @once<style>{!! preg_replace('/opacity:\s*1(?=[;}])/', 'opacity:var(--lu-toast-opacity,1)', \Jeremykenedy\LaravelToast\Support\ToastAnimations::css()) !!}</style>@endonce
@endif
