const cardIcons = {
    @foreach(['id' => 'id', 'name' => 'user', 'email' => 'mail', 'role' => 'role', 'created' => 'clock', 'updated' => 'clock'] as $column => $icon)
    @json(__('laravelusers::laravelusers.users-table.'.$column)): @json($icon),
    @endforeach
    @json(__('laravelusers::ui.presence')): 'user',
    @json(__('laravelusers::ui.last_login_at')): 'clock',
    @json(__('laravelusers::ui.login_details')): 'device'
};
