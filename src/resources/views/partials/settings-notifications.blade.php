<fieldset class="lu-settings-notifications" @if(!\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_notifications')) disabled @endif>
    <legend class="lu-title-heading"><span class="lu-title-icon">@include('laravelusers::partials.icon', ['name' => 'notifications'])</span><span>{{ __('laravelusers::ui.settings_notifications') }}</span></legend>
    @if(\jeremykenedy\laravelusers\Support\UserNotifications::toastInstalled())
        <div class="lu-notification-driver">
            <label for="settings-notifications">{{ __('laravelusers::ui.settings_notification_style') }}</label>
            <select id="settings-notifications" name="notifications_driver" class="{{ $modern ? 'lu-input' : 'form-control' }}">@foreach(['alert', 'toast', 'both'] as $driver)<option value="{{ $driver }}" @if(old('notifications_driver', config('laravelusers.notifications.driver', 'alert')) === $driver) selected @endif>{{ __('laravelusers::ui.settings_notification_'.$driver) }}</option>@endforeach</select>
        </div>
    @endif
    <label class="lu-settings-check lu-notification-dismiss"><input type="hidden" name="notifications_dismissible" value="0"><input type="checkbox" name="notifications_dismissible" value="1" @if(old('notifications_dismissible', config('laravelusers.notifications.dismissible', true))) checked @endif><span>{{ __('laravelusers::ui.settings_dismissible') }}</span></label>
    <div class="lu-notification-preview-actions"><button type="button" disabled class="{{ $modern ? 'lu-button' : 'btn btn-secondary' }}" data-lu-preview-notification>@include('laravelusers::partials.icon', ['name' => 'notifications']) {{ __('laravelusers::ui.notification_preview') }}</button></div>
    <template data-lu-preview-alert-template><div class="lu-flash alert alert-success" role="status" data-lu-notification-preview><span>{{ __('laravelusers::ui.notification_preview_message') }}</span><button type="button" disabled data-lu-dismiss-alert aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button></div></template>
    @if(\jeremykenedy\laravelusers\Support\UserNotifications::toastInstalled())
        @include('laravelusers::partials.toast-animations')
        <template data-lu-preview-toast-template><div class="lu-toast" data-laravel-toast="blade" data-lu-toast data-type="success" role="status" data-lu-notification-preview><div class="lu-toast-progress"><span data-lu-toast-progress></span></div><div class="lu-toast-body"><span class="lu-toast-icon">@include('laravelusers::partials.icon', ['name' => 'check'])</span><div class="lu-toast-message"><span>{{ __('laravelusers::ui.notification_preview_message') }}</span></div><button type="button" disabled data-lu-dismiss-toast aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button></div></div></template>
        @include('laravelusers::partials.toast-settings')
    @endif
</fieldset>
