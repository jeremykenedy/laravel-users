@php($badgeClass = 'badge badge-primary lu-badge'.(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'bootstrap5' ? ' bg-primary' : ''))
<section class="lu-profile" aria-label="{{ __('laravelusers::ui.profile') }}" @include('laravelusers::partials.appearance-attributes', ['appearance' => $profileAppearance ?? null])>
    <header class="lu-profile-header lu-page-heading">
        <h1 class="lu-list-title">@include('laravelusers::partials.icon', ['name' => 'user']) <span class="lu-title-text">{{ __('laravelusers::laravelusers.showing-user-title', ['name' => $user->name]) }}</span></h1>
        <a class="{{ $modern ? 'lu-button lu-secondary' : 'btn btn-info' }}" href="{{ route('users') }}">@include('laravelusers::partials.icon', ['name' => 'reply']) {{ __('laravelusers::ui.back') }}</a>
    </header>
    <div class="lu-profile-body">
        @include('laravelusers::partials.user-identity')
        <dl class="lu-profile-details">
            @foreach(['id' => 'id', 'name' => 'user', 'email' => 'mail', 'created_at' => 'clock', 'updated_at' => 'clock'] as $field => $icon)
                <div class="lu-detail"><dt>@include('laravelusers::partials.icon', ['name' => $icon]) {{ strip_tags(__('laravelusers::laravelusers.show-user.'.str_replace('_at', '', $field))) }}</dt><dd>@if(str_ends_with($field, '_at'))@include('laravelusers::partials.date', ['value' => $user->$field])@else{{ $user->$field }}@endif</dd></div>
            @endforeach
            @if(config('laravelusers.rolesEnabled'))
                <div class="lu-detail"><dt>@include('laravelusers::partials.icon', ['name' => 'role']) {{ __('laravelusers::laravelusers.show-user.labelRole') }}</dt><dd>@foreach($user->roles as $role)<span class="{{ $badgeClass }}">{{ $role->name }}</span> @endforeach</dd></div>
                @if(config('laravelusers.showRoleLevels', true) && isset($roleLevel))<div class="lu-detail"><dt>@include('laravelusers::partials.icon', ['name' => 'role']) {{ strip_tags(trans_choice('laravelusers::laravelusers.show-user.labelAccessLevel', 1)) }}</dt><dd>@foreach(range(5, 1) as $level)@if($roleLevel >= $level)<span class="{{ $badgeClass }}">{{ $level }}</span> @endif @endforeach</dd></div>@endif
            @endif
            @if(isset($directPermissions))<div class="lu-detail"><dt>@include('laravelusers::partials.icon', ['name' => 'role']) {{ __('laravelusers::ui.direct_permissions') }}</dt><dd>@foreach($directPermissions as $permission)<span class="{{ $badgeClass }}">{{ $permission->name }}</span> @endforeach</dd></div>@endif
            @if(config('laravelusers.activity.login', false) || config('laravelusers.activity.online', false))@include('laravelusers::partials.user-activity')@endif
        </dl>
    </div>
    <footer class="lu-profile-actions">
        @if(\jeremykenedy\laravelusers\Support\UserAccess::allows('edit_users'))<a class="{{ $modern ? 'lu-button' : 'btn btn-info' }}" href="{{ route('users.edit', $user->id) }}">@include('laravelusers::partials.icon', ['name' => 'edit']) {{ __('laravelusers::ui.edit') }}</a>@endif
        @include('laravelusers::partials.impersonate-button', ['target' => $user, 'modern' => $modern])
        @if($modern)
            @include('laravelusers::modern.delete')
        @elseif(\jeremykenedy\laravelusers\Support\UserAccess::allows('delete_users') && (string) Auth::id() !== (string) $user->id)
            <form method="POST" action="{{ route('user.destroy', $user->id) }}">@csrf @method('DELETE')<button type="{{ config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" class="btn btn-danger" @if(config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" @endif data-title="{{ __('laravelusers::modals.delete_user_title') }}" data-message="{{ __('laravelusers::ui.confirm_delete', ['name' => $user->name]) }}">@include('laravelusers::partials.icon', ['name' => 'delete']) {{ __('laravelusers::ui.delete') }}</button></form>
        @endif
    </footer>
    <x-laravelusers::email-actions :user="$user" />
</section>
