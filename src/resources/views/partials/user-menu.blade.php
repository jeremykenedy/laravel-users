@auth
    @include('laravelusers::partials.impersonation-banner')
    @if(($canManageUsers ?? false) || ($accountPageEnabled ?? false) || (($showLogout ?? config('laravelusers.showLogout', true)) && Route::has($logoutRoute ?? 'logout')))
        <details class="lu-user-menu">
            <summary class="lu-user-menu-toggle">@if($navigationAvatar)@include('laravelusers::partials.avatar', ['avatar' => $navigationAvatar, 'navigation' => true])@else@include('laravelusers::partials.icon', ['name' => 'user'])@endif <span>{{ Auth::user()->name }}</span><span aria-hidden="true" class="lu-user-menu-caret"></span></summary>
            <div class="lu-user-menu-items">
                @if(config('laravelusers.activity.login', false))
                    <div class="lu-user-menu-login" role="group" aria-label="{{ __('laravelusers::ui.login_details') }}">
                        <div class="lu-user-menu-login-row"><span class="lu-user-menu-login-icon">@include('laravelusers::partials.icon', ['name' => 'clock'])</span><span>{{ __('laravelusers::ui.last_login_at') }}</span><span>@include('laravelusers::partials.date', ['value' => $navigationLastLogin->last_login_at ?? null, 'empty' => __('laravelusers::ui.no_logins')])</span></div>
                        @if($navigationLastLogin)
                            @foreach(['ip_address', 'device', 'os', 'browser'] as $field)
                                @if($navigationLastLogin->$field)
                                    <div class="lu-user-menu-login-row"><span class="lu-user-menu-login-icon">@include('laravelusers::partials.icon', ['name' => $navigationLoginIcons[$field]])</span><span>{{ __('laravelusers::ui.'.$field) }}</span><span>{{ $navigationLastLogin->$field }}</span></div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                @endif
                @if($canManageUsers ?? false)<a href="{{ route('users') }}">@include('laravelusers::partials.icon', ['name' => 'users']) {{ __('laravelusers::ui.manage_users') }}</a>@endif
                @if($accountPageEnabled ?? false)<a href="{{ route('users.account') }}">@include('laravelusers::partials.icon', ['name' => 'settings']) {{ __('laravelusers::ui.account_menu_label') }}</a>@endif
                @if(($showLogout ?? config('laravelusers.showLogout', true)) && Route::has($logoutRoute ?? 'logout'))<form method="POST" action="{{ route($logoutRoute ?? 'logout') }}">@csrf<button type="submit">@include('laravelusers::partials.icon', ['name' => 'logout']) {{ __('laravelusers::ui.logout') }}</button></form>@endif
            </div>
        </details>
    @else
        <span class="lu-user-menu-toggle">@if($navigationAvatar)@include('laravelusers::partials.avatar', ['avatar' => $navigationAvatar, 'navigation' => true])@else@include('laravelusers::partials.icon', ['name' => 'user'])@endif {{ Auth::user()->name }}</span>
    @endif
@endauth
