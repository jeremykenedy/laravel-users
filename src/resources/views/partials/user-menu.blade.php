@auth
    @if(($canManageUsers ?? false) || ($accountPageEnabled ?? false) || (($showLogout ?? config('laravelusers.showLogout', true)) && Route::has($logoutRoute ?? 'logout')))
        <details class="lu-user-menu">
            <summary class="lu-user-menu-toggle">@if($navigationAvatar)@include('laravelusers::partials.avatar', ['avatar' => $navigationAvatar, 'navigation' => true])@else@include('laravelusers::partials.icon', ['name' => 'user'])@endif <span>{{ Auth::user()->name }}</span><span aria-hidden="true" class="lu-user-menu-caret"></span></summary>
            <div class="lu-user-menu-items">
                @if($canManageUsers ?? false)<a href="{{ route('users') }}">@include('laravelusers::partials.icon', ['name' => 'users']) {{ __('laravelusers::ui.manage_users') }}</a>@endif
                @if($accountPageEnabled ?? false)<a href="{{ route('users.account') }}">@include('laravelusers::partials.icon', ['name' => 'settings']) {{ __('laravelusers::ui.account_menu_label') }}</a>@endif
                @if(($showLogout ?? config('laravelusers.showLogout', true)) && Route::has($logoutRoute ?? 'logout'))<form method="POST" action="{{ route($logoutRoute ?? 'logout') }}">@csrf<button type="submit">@include('laravelusers::partials.icon', ['name' => 'logout']) {{ __('laravelusers::ui.logout') }}</button></form>@endif
            </div>
        </details>
    @else
        <span class="lu-user-menu-toggle">@if($navigationAvatar)@include('laravelusers::partials.avatar', ['avatar' => $navigationAvatar, 'navigation' => true])@else@include('laravelusers::partials.icon', ['name' => 'user'])@endif {{ Auth::user()->name }}</span>
    @endif
@endauth
