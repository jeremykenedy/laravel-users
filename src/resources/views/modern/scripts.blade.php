@php
    $options = [
        'tooltips' => (bool) config('laravelusers.tooltipsEnabled', true),
        'deleteTitle' => __('laravelusers::modals.delete_user_title'),
        'avatarColumn' => (bool) config('laravelusers.avatar.enabled', false),
        'bulk' => (bool) config('laravelusers.bulkActions', false),
        'roles' => (bool) config('laravelusers.rolesEnabled'),
        'createdColumn' => (bool) config('laravelusers.showCreatedColumn', true),
        'updatedColumn' => (bool) config('laravelusers.showUpdatedColumn', true),
        'onlineColumn' => (bool) (config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', false)),
        'loginColumn' => (bool) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false)),
        'loginDetailsColumn' => (bool) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false)),
        'emailLinks' => (bool) config('laravelusers.emailLinks', false),
        'currentUser' => Auth::id(),
        'usersUrl' => url('users'),
        'searchDelay' => max(0, (int) config('laravelusers.searchDebounce', 2000)),
        'searchDebounce' => (bool) config('laravelusers.searchDebounceEnabled', false),
        'searching' => __('laravelusers::ui.searching'),
        'selectable' => \jeremykenedy\laravelusers\Support\UserAccess::selectable(),
        'selectUser' => __('laravelusers::ui.select_user', ['name' => ':name']),
        'viewUser' => __('laravelusers::ui.view_user'),
        'emailUser' => __('laravelusers::ui.email_user'),
        'online' => __('laravelusers::ui.online'),
        'noLogins' => __('laravelusers::ui.no_logins'),
        'lookupIp' => __('laravelusers::ui.lookup_ip'),
        'confirmDelete' => __('laravelusers::ui.confirm_delete', ['name' => ':name']),
        'noResults' => __('laravelusers::laravelusers.search.no-results'),
        'results' => __('laravelusers::ui.results', ['count' => ':count']),
        'searchError' => __('laravelusers::ui.search_error'),
    ];
@endphp
<script type="application/json" id="lu-page-options">@json($options)</script>
@include('laravelusers::partials.asset', ['name' => 'users.js'])
