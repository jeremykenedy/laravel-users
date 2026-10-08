@if(\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_settings'))
    <a class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-light btn-sm' }} lu-settings-button" href="{{ route('users.settings') }}" aria-label="{{ __('laravelusers::ui.settings') }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.settings') }}" @endif>@include('laravelusers::partials.icon', ['name' => 'settings'])</a>
@endif
