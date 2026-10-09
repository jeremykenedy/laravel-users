@extends('laravelusers::modern.page')
@section('template_title', __('laravelusers::ui.account_title'))
@section('users_content')
<section class="lu-profile lu-account-card" @include('laravelusers::partials.appearance-attributes', ['appearance' => $profileAppearance ?? null])>
    <header class="lu-profile-header lu-page-heading"><h1 class="lu-list-title">@include('laravelusers::partials.icon', ['name' => 'user']) <span class="lu-title-text">{{ __('laravelusers::ui.account_title') }}</span></h1></header>
    <div class="lu-account-body">
        <aside class="lu-account-identity">
            @include('laravelusers::partials.user-identity')
            <div class="lu-account-summary">
                <h2>{{ $fullName ?: $user->name }}</h2>
                <p>{{ __('laravelusers::ui.account_private') }}</p>
                @if($pendingEmail)<p role="status">{{ __('laravelusers::ui.account_email_pending_to', ['email' => $pendingEmail->new_email]) }}</p>@endif
            </div>
        </aside>
        <div class="lu-account-content">
            @if(!$accountEditable)
                <p class="lu-muted">{{ __('laravelusers::ui.account_readonly') }}</p>
            @else
                @php($accountTabs = array_filter([
                    'profile' => config('laravelusers.account.profile', true),
                    'avatar' => config('laravelusers.account.avatar', true),
                    'appearance' => config('laravelusers.account.appearance', true),
                    'account' => config('laravelusers.account.email', true),
                    'security' => config('laravelusers.account.password', true),
                    'admin' => config('laravelusers.account.delete', true),
                ]))
                @php($activeAccountTab = old('account_tab', old('section', session('account_section', array_key_first($accountTabs)))))
                <nav class="lu-settings-tabs lu-account-tabs" role="tablist" aria-label="{{ __('laravelusers::ui.account_sections') }}" data-lu-account-tabs>
                    @foreach($accountTabs as $tab => $enabled)
                        <button type="button" role="tab" id="lu-account-tab-{{ $tab }}" aria-controls="lu-account-panel-{{ $tab }}" aria-selected="{{ $tab === $activeAccountTab ? 'true' : 'false' }}" tabindex="{{ $tab === $activeAccountTab ? '0' : '-1' }}" data-lu-account-tab="{{ $tab }}">@include('laravelusers::partials.icon', ['name' => ['profile' => 'user', 'avatar' => 'user', 'appearance' => 'settings', 'account' => 'mail', 'security' => 'lock', 'admin' => 'delete'][$tab]]) {{ __('laravelusers::ui.account_tab_'.$tab) }}</button>
                    @endforeach
                </nav>
                <div class="lu-account-panels">
                    @if(config('laravelusers.account.profile', true))
                        <section id="lu-account-panel-profile" role="tabpanel" aria-labelledby="lu-account-tab-profile" data-lu-account-panel="profile" @if($activeAccountTab !== 'profile') hidden @endif>
                            <form method="POST" action="{{ route('users.account.update') }}" class="lu-account-section">@csrf @method('PUT')<input type="hidden" name="section" value="profile"><input type="hidden" name="account_tab" value="profile"><h2>@include('laravelusers::partials.icon', ['name' => 'user']) {{ __('laravelusers::ui.account_profile') }}</h2><div class="lu-account-grid"><x-laravelusers::account-input name="username" :label="__('laravelusers::ui.account_username')" :value="$user->getAttribute(config('laravelusers.account.username_column', 'name'))" /><x-laravelusers::account-input name="full_name" :label="__('laravelusers::ui.account_name')" :value="$fullName ?: $user->name" /></div><button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.account_save_profile') }}</button></form>
                        </section>
                    @endif
                    @if(config('laravelusers.account.avatar', true))
                        <section id="lu-account-panel-avatar" role="tabpanel" aria-labelledby="lu-account-tab-avatar" data-lu-account-panel="avatar" @if($activeAccountTab !== 'avatar') hidden @endif>
                            <form method="POST" action="{{ route('users.account.update') }}" class="lu-account-section" data-lu-account-return-tab="avatar">@csrf @method('PUT')<input type="hidden" name="section" value="appearance"><input type="hidden" name="account_tab" value="avatar"><h2>@include('laravelusers::partials.icon', ['name' => 'user']) {{ __('laravelusers::ui.account_tab_avatar') }}</h2>@include('laravelusers::partials.avatar-source', ['modern' => true])<button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.account_save_avatar') }}</button></form>
                        </section>
                    @endif
                    @if(config('laravelusers.account.appearance', true))
                        <section id="lu-account-panel-appearance" role="tabpanel" aria-labelledby="lu-account-tab-appearance" data-lu-account-panel="appearance" @if($activeAccountTab !== 'appearance') hidden @endif>
                            <form method="POST" action="{{ route('users.account.update') }}" class="lu-account-section">@csrf @method('PUT')<input type="hidden" name="section" value="appearance"><input type="hidden" name="account_tab" value="appearance"><h2>@include('laravelusers::partials.icon', ['name' => 'settings']) {{ __('laravelusers::ui.settings_appearance') }}</h2>@include('laravelusers::partials.user-appearance', ['modern' => true])<button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'save']) {{ __('laravelusers::ui.account_save_appearance') }}</button></form>
                        </section>
                    @endif
                    @if(config('laravelusers.account.email', true))
                        <section id="lu-account-panel-account" role="tabpanel" aria-labelledby="lu-account-tab-account" data-lu-account-panel="account" @if($activeAccountTab !== 'account') hidden @endif>
                            <form method="POST" action="{{ route('users.account.update') }}" class="lu-account-section">@csrf @method('PUT')<input type="hidden" name="section" value="email"><input type="hidden" name="account_tab" value="account"><h2>@include('laravelusers::partials.icon', ['name' => 'mail']) {{ __('laravelusers::ui.account_email') }}</h2><p class="lu-muted">{{ __('laravelusers::ui.account_email_both') }}</p><div class="lu-account-grid"><x-laravelusers::account-input name="email" type="email" icon="mail" :label="__('laravelusers::ui.account_new_email')" :value="$user->email" /><x-laravelusers::account-input name="current_password" id="email-current-password" type="password" icon="lock" :label="__('laravelusers::ui.account_current_password')" /></div><button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'mail']) {{ __('laravelusers::ui.account_send_confirmation') }}</button></form>
                        </section>
                    @endif
                    @if(config('laravelusers.account.password', true))
                        <section id="lu-account-panel-security" role="tabpanel" aria-labelledby="lu-account-tab-security" data-lu-account-panel="security" @if($activeAccountTab !== 'security') hidden @endif>
                            <form method="POST" action="{{ route('users.account.update') }}" class="lu-account-section">@csrf @method('PUT')<input type="hidden" name="section" value="password"><input type="hidden" name="account_tab" value="security"><h2>@include('laravelusers::partials.icon', ['name' => 'lock']) {{ __('laravelusers::ui.account_password') }}</h2><x-laravelusers::account-input name="current_password" id="password-current-password" type="password" icon="lock" :label="__('laravelusers::ui.account_current_password')" /><div class="lu-account-grid"><div><x-laravelusers::account-input name="password" type="password" icon="lock" :label="__('laravelusers::ui.account_new_password')" />@include('laravelusers::partials.password-meter', ['creating' => false])</div><div><x-laravelusers::account-input name="password_confirmation" type="password" icon="lock" :label="__('laravelusers::forms.create_user_label_pw_confirmation')" />@include('laravelusers::partials.password-confirmation')</div></div><button class="lu-button" type="submit">@include('laravelusers::partials.icon', ['name' => 'lock']) {{ __('laravelusers::ui.account_change_password') }}</button></form>
                        </section>
                    @endif
                    @if(config('laravelusers.account.delete', true))
                        <section id="lu-account-panel-admin" role="tabpanel" aria-labelledby="lu-account-tab-admin" data-lu-account-panel="admin" @if($activeAccountTab !== 'admin') hidden @endif>
                            <section class="lu-account-section lu-account-danger"><h2>@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.account_delete') }}</h2><p class="lu-muted">{{ __('laravelusers::ui.account_delete_warning') }}</p><button class="lu-button lu-danger" type="button" data-lu-account-delete>@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.account_delete') }}</button></section>
                        </section>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
@if($accountEditable && config('laravelusers.account.delete', true))
<dialog id="lu-account-delete-dialog" class="lu-email-dialog" aria-labelledby="lu-account-delete-title"><header class="lu-email-heading"><h2 id="lu-account-delete-title">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.account_delete') }}</h2><button class="lu-email-close" type="button" data-lu-account-dismiss aria-label="{{ __('laravelusers::ui.close') }}">@include('laravelusers::partials.icon', ['name' => 'close'])</button></header><form class="lu-account-delete-form" method="POST" action="{{ route('users.account.delete') }}">@csrf @method('DELETE')<div class="lu-email-body"><p>{{ __('laravelusers::ui.account_delete_warning') }}</p><x-laravelusers::account-input name="current_password" id="delete-current-password" type="password" icon="lock" :label="__('laravelusers::ui.account_current_password')" /><x-laravelusers::account-input name="confirmation" icon="delete" :label="__('laravelusers::ui.account_delete_confirm')" autocomplete="off" /></div><footer class="lu-email-footer"><button class="lu-button lu-secondary" type="button" data-lu-account-dismiss>{{ __('laravelusers::forms.cancel') }}</button><button class="lu-button lu-danger" type="submit" data-lu-account-delete-confirm disabled>@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.account_delete') }}</button></footer></form></dialog>
@endif
@endsection
