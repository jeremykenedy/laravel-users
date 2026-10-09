<div class="lu-profile-identity">
    @if(config('laravelusers.showProfileAvatar', true) && isset($userAvatar))@include('laravelusers::partials.avatar', ['avatar' => $userAvatar])@endif
    <div><h2>{{ $user->name }}</h2><p>@if(config('laravelusers.emailLinks', false))<a href="mailto:{{ $user->email }}" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.email_user') }}" @endif>{{ $user->email }}</a>@else{{ $user->email }}@endif</p>@if(isset($rolesEnabled) && $rolesEnabled)<p>@foreach($user->roles as $role)<span class="badge badge-primary lu-badge">{{ $role->name }}</span> @endforeach</p>@if(config('laravelusers.showRoleLevels', true) && isset($roleLevel))<p>{{ __('laravelusers::ui.role_level', ['level' => $roleLevel]) }}</p>@endif @endif</div>
</div>
