<fieldset class="lu-settings-notifications" @if(!\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_notifications')) disabled @endif>
    <legend class="lu-title-heading"><span class="lu-title-icon">@include('laravelusers::partials.icon', ['name' => 'notifications'])</span><span>{{ __('laravelusers::ui.settings_notifications') }}</span></legend>
    @if(\jeremykenedy\laravelusers\Support\UserNotifications::toastInstalled())
        <div class="lu-notification-driver">
            <label for="settings-notifications">{{ __('laravelusers::ui.settings_notification_style') }}</label>
            <select id="settings-notifications" name="notifications_driver" class="{{ $modern ? 'lu-input' : 'form-control' }}">@foreach(['alert', 'toast', 'both'] as $driver)<option value="{{ $driver }}" @if(old('notifications_driver', config('laravelusers.notifications.driver', 'alert')) === $driver) selected @endif>{{ __('laravelusers::ui.settings_notification_'.$driver) }}</option>@endforeach</select>
        </div>
    @endif
    <label class="lu-settings-check lu-notification-dismiss"><input type="hidden" name="notifications_dismissible" value="0"><input type="checkbox" name="notifications_dismissible" value="1" @if(old('notifications_dismissible', config('laravelusers.notifications.dismissible', true))) checked @endif><span>{{ __('laravelusers::ui.settings_dismissible') }}</span></label>
    @if(\jeremykenedy\laravelusers\Support\UserNotifications::toastInstalled())
        @include('laravelusers::partials.toast-settings')
    @endif
</fieldset>
