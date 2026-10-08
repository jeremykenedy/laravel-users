<script>
    $(function() {
        var timer;
        var pendingRequest;
        var delay = @json(max(0, (int) config('laravelusers.searchDebounce', 2000)));
        var cardTitle = $('#card_title');
        var usersTable = $('#users_table');
        var resultsContainer = $('#search_results');
        var usersCount = $('#user_count');
        var clearSearchTrigger = $('.clear-search');
        var searchform = $('#search_users');
        var searchformInput = $('#user_search_box');
        clearSearchTrigger.toggle(searchformInput.val().length > 0);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html().replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        searchform.submit(function(e) {
            e.preventDefault();
            clearTimeout(timer);
            if (pendingRequest) pendingRequest.abort();
            resultsContainer.html('');
            usersTable.hide();
            clearSearchTrigger.toggle(searchformInput.val().length > 0);
            let noResulsHtml = '<tr><td colspan="{{ 6 + (int) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', true)) + (int) config('laravelusers.bulkActions', false) + (int) config('laravelusers.avatar.enabled', false) + (int) config('laravelusers.showCreatedColumn', true) + (int) config('laravelusers.showUpdatedColumn', true) + (int) config('laravelusers.rolesEnabled') + (int) (config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', true)) + (int) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', true)) }}">{{ __('laravelusers::laravelusers.search.no-results') }}</td></tr>';

            pendingRequest = $.ajax({
                type:'POST',
                url: "{{ route('search-users') }}",
                data: searchform.serialize(),
                success: function (result) {
                    let payload = typeof result === 'string' ? JSON.parse(result) : result;
                    let jsonData = Array.isArray(payload) ? payload : payload.users;
                    let activity = payload.activity || {};
                    let avatars = payload.avatars || {};
                    if (jsonData.length != 0) {
                        $.each(jsonData, function(index, val) {
                            let details = activity[val.id] || {};
                            let loginFields = ['device', 'os', 'browser', 'ip_address'].map(field => escapeHtml(details[field])).filter(Boolean);
                            let loginDetails = loginFields.map(value => '<span>' + value + '</span>').join('');
                            let avatar = avatars[val.id] || { initials: '?', size: 40, fallback: 'icon' };
                            let avatarHtml = '<span class="lu-avatar" style="width:' + Number(avatar.size) + 'px;height:' + Number(avatar.size) + 'px" aria-hidden="true">' +
                                (avatar.fallback === 'initials' ? escapeHtml(avatar.initials) : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>') +
                                (avatar.src ? '<img src="' + escapeHtml(avatar.src) + '" alt="" loading="lazy" referrerpolicy="no-referrer">' : '') + '</span>';
                            val = Object.assign({}, val, {
                                id: encodeURIComponent(val.id),
                                name: escapeHtml(val.name),
                                email: escapeHtml(val.email),
                                created_at: escapeHtml(val.created_at),
                                updated_at: escapeHtml(val.updated_at)
                            });
                            let rolesHtml = '';
                            let roleClass = '';
                            let showCellHtml = '<a class="btn btn-sm btn-success btn-block" href="users/' + val.id + '" data-toggle="tooltip" title="{{ trans("laravelusers::laravelusers.tooltips.show") }}">{!! trans("laravelusers::laravelusers.buttons.show") !!}</a>';
                            let editCellHtml = '<a class="btn btn-sm btn-info btn-block" href="users/' + val.id + '/edit" data-toggle="tooltip" title="{{ trans("laravelusers::laravelusers.tooltips.edit") }}">{!! trans("laravelusers::laravelusers.buttons.edit") !!}</a>';
                            let deleteCellHtml = '<form method="POST" action="users/'+ val.id +'" accept-charset="UTF-8" data-toggle="tooltip" title="Delete">' +
                                    '<input type="hidden" name="_method" value="DELETE">' +
                                    '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                                    '<button class="btn btn-danger btn-sm" type="{{ config('laravelusers.confirmDelete', true) ? 'button' : 'submit' }}" style="width: 100%;" @if(config('laravelusers.confirmDelete', true)) data-toggle="modal" data-target="#confirmDelete" @endif data-title="Delete User" data-message="{{ trans("laravelusers::modals.delete_user_message", ["user" => "'+val.name+'"]) }}">' +
                                        '{!! trans("laravelusers::laravelusers.buttons.delete") !!}' +
                                    '</button>' +
                                '</form>';

                            $.each(val.roles || [], function(roleIndex, role) {
                                if (role.name == "User") {
                                    roleClass = 'primary';
                                } else if (role.name == "Admin") {
                                    roleClass = 'warning';
                                } else if (role.name == "Unverified") {
                                    roleClass = 'danger';
                                } else {
                                    roleClass = 'dark';
                                };
                                rolesHtml = '<span class="badge badge-' + roleClass + '">' + escapeHtml(role.name) + '</span> ';
                            });
                            resultsContainer.append('<tr>' +
                                '@if(config("laravelusers.avatar.enabled", false))<td>' + avatarHtml + '</td>@endif' +
                                '@if(config("laravelusers.bulkActions", false))<td><input type="checkbox" data-lu-select value="' + val.id + '" aria-label="{{ __("laravelusers::ui.select_user", ["name" => "'+val.name+'"]) }}"' + (String(val.id) === String(@json(Auth::id())) ? ' disabled' : '') + '></td>@endif' +
                                '<td>' + val.id + '</td>' +
                                '<td><a href="users/' + val.id + '" @if(config("laravelusers.tooltipsEnabled", true)) title="{{ __("laravelusers::ui.view_user") }}" data-toggle="tooltip" @endif>' + val.name + '</a></td>' +
                                '<td class="hidden-xs">' + (@json((bool) config('laravelusers.emailLinks', true)) ? '<a href="mailto:' + val.email + '" @if(config("laravelusers.tooltipsEnabled", true)) title="{{ __("laravelusers::ui.email_user") }}" data-toggle="tooltip" @endif>' + val.email + '</a>' : val.email) + '</td>' +
                                '@if(config("laravelusers.rolesEnabled"))<td class="hidden-sm hidden-xs"> ' + rolesHtml  +'</td>@endif' +
                                '@if(config("laravelusers.activity.online", false) && config("laravelusers.showOnlineColumn", true))<td data-lu-value="' + (details.online === true ? 'online' : 'offline') + '">' + (details.online === true ? '<span class="badge badge-success">{{ __("laravelusers::ui.online") }}</span>' : '') + '</td>@endif' +
                                '@if(config("laravelusers.showCreatedColumn", true))<td class="hidden-sm hidden-xs hidden-md" data-lu-date="' + val.created_at + '">' + val.created_at + '</td>@endif' +
                                '@if(config("laravelusers.showUpdatedColumn", true))<td class="hidden-sm hidden-xs hidden-md" data-lu-date="' + val.updated_at + '">' + val.updated_at + '</td>@endif' +
                                '@if(config("laravelusers.activity.login", false) && config("laravelusers.showLastLoginColumn", true))<td data-lu-date="' + escapeHtml(details.last_login_at || '') + '">' + escapeHtml(details.last_login_at || '') + '</td>@endif' +
                                '@if(config("laravelusers.activity.login", false) && config("laravelusers.showLastLoginDetailsColumn", true))<td><span class="lu-login-details" title="' + loginFields.join(' / ') + '">' + loginDetails + '</span></td>@endif' +
                                '<td>' + deleteCellHtml + '</td>' +
                                '<td>' + showCellHtml + '</td>' +
                                '<td>' + editCellHtml + '</td>' +
                            '</tr>');
                        });
                    } else {
                        resultsContainer.append(noResulsHtml);
                    };
                    usersCount.html(jsonData.length + " {!! trans('laravelusers::laravelusers.search.found-footer') !!}");
                    cardTitle.html("{!! trans('laravelusers::laravelusers.search.title') !!}");
                    document.getElementById('laravelusers').dispatchEvent(new Event('lu:rows'));
                    @if(config('laravelusers.tooltipsEnabled', true))
                        if ($.fn.tooltip) resultsContainer.find('[data-toggle="tooltip"]').tooltip();
                    @endif
                },
                error: function (response, status, error) {
                    if (response.status === 422) {
                        resultsContainer.append(noResulsHtml);
                        usersCount.html(0 + " {!! trans('laravelusers::laravelusers.search.found-footer') !!}");
                        cardTitle.html("{!! trans('laravelusers::laravelusers.search.title') !!}");
                    };
                },
            });
        });
        searchformInput.on('input', function(event) {
            clearTimeout(timer);
            if (pendingRequest) pendingRequest.abort();
            if ($('#user_search_box').val() != '') {
                clearSearchTrigger.show();
                if (!@json((bool) config('laravelusers.searchDebounceEnabled', true))) return;
                timer = setTimeout(function () { searchform.trigger('submit'); }, delay);
            } else {
                clearSearchTrigger.hide();
                resultsContainer.html('');
                usersTable.show();
                cardTitle.html("{!! trans('laravelusers::laravelusers.showing-all-users') !!}");
                usersCount.html("{!! trans_choice('laravelusers::laravelusers.users-table.caption', 1, ['userscount' => $users->count()]) !!}");
            };
        });
        clearSearchTrigger.click(function(e) {
            e.preventDefault();
            clearTimeout(timer);
            if (pendingRequest) pendingRequest.abort();
            clearSearchTrigger.hide();
            usersTable.show();
            resultsContainer.html('');
            searchformInput.val('');
            cardTitle.html("{!! trans('laravelusers::laravelusers.showing-all-users') !!}");
            usersCount.html("{!! trans_choice('laravelusers::laravelusers.users-table.caption', 1, ['userscount' => $users->count()]) !!}");
        });
    });
</script>
