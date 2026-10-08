<div class="lu-settings-tabs" role="tablist" aria-label="{{ __('laravelusers::ui.settings') }}">
@foreach(['appearance' => 'appearance', 'notifications' => 'notifications', 'access' => 'role', 'accounts' => 'user', 'emails' => 'mail', 'cleanup' => 'delete', 'packages' => 'package'] as $tab => $icon)
@if(($tab !== 'access' || $accessAvailable) && ($tab !== 'accounts' || \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_account_access')) && ($tab !== 'emails' || \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_email_templates')) && ($tab !== 'cleanup' || (config('laravelusers.softDeletedEnabled', false) && \jeremykenedy\laravelusers\Support\UserAccess::allows('edit_cleanup') && \jeremykenedy\laravelusers\Support\UserAccess::allows('force_delete'))) && ($tab !== 'packages' || $packageManagementAllowed))
<button id="lu-tab-{{ $tab }}" type="button" role="tab" aria-controls="lu-settings-{{ $tab }}" aria-selected="{{ $tab === 'appearance' ? 'true' : 'false' }}" tabindex="{{ $tab === 'appearance' ? '0' : '-1' }}" data-lu-settings-tab="{{ $tab }}">@include('laravelusers::partials.icon', ['name' => $icon]) {{ __('laravelusers::ui.settings_tab_'.$tab) }}</button>
@endif
@endforeach
</div>
