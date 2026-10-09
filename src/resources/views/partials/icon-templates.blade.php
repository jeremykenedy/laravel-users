@if(config('laravelusers.iconsEnabled', true))
    @foreach(['sort', 'columns', 'filter', 'id', 'user', 'mail', 'clock', 'role', 'device', 'browser', 'network', 'chrome', 'safari', 'firefox', 'edge', 'windows', 'apple', 'android', 'linux'] as $icon)
        <template data-lu-icon-template="{{ $icon }}">@include('laravelusers::partials.icon', ['name' => $icon])</template>
    @endforeach
@endif
