<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    const tooltips = @json((bool) config('laravelusers.tooltipsEnabled', true));
    function buttonLabels() {
        root.querySelectorAll('.lu-button').forEach(function (button) {
            const label = button.textContent.trim();
            if (label && !button.hasAttribute('aria-label')) button.setAttribute('aria-label', label);
            if (label && tooltips) button.title = label;
        });
    }
    buttonLabels();
    root.addEventListener('lu:rows', buttonLabels);
    const modal = root.querySelector('#lu-confirmation');
    let pendingForm;
    let confirmedForm;
    let previousFocus;
    root.addEventListener('submit', function (event) {
        const form = event.target;
        if (!form.hasAttribute('data-lu-confirm') || form === confirmedForm || !modal) return;
        event.preventDefault();
        pendingForm = form;
        previousFocus = document.activeElement;
        modal.querySelector('#lu-confirm-title').textContent = form.dataset.luConfirmTitle || @json(__('laravelusers::modals.delete_user_title'));
        modal.querySelector('#lu-confirm-message').textContent = form.dataset.luConfirm;
        const action = form.querySelector('[name="action"]');
        const deletion = form.querySelector('[name="_method"]')?.value === 'DELETE' || (action && ['delete', 'force_delete'].includes(action.value));
        modal.dataset.luDelete = deletion ? 'true' : 'false';
        modal.dispatchEvent(new CustomEvent('lu:delete-modal', { bubbles: true, detail: { action: deletion && (!action || action.value === 'delete') && !/\/force(?:\?|$)/.test(form.action) ? 'delete' : null } }));
        modal.querySelector('[data-lu-delete-icon]').hidden = !deletion;
        modal.querySelector('[data-lu-save-icon]').hidden = deletion;
        modal.showModal();
    });
    if (modal) {
        modal.querySelectorAll('[data-lu-dismiss]').forEach(button => button.addEventListener('click', () => modal.close()));
        modal.addEventListener('close', function () {
            pendingForm = null;
            if (previousFocus && previousFocus.isConnected) previousFocus.focus();
        });
        modal.querySelector('#lu-confirm-submit').addEventListener('click', function () {
            if (!pendingForm) return;
            confirmedForm = pendingForm;
            modal.dispatchEvent(new CustomEvent('lu:delete-confirm', { bubbles: true, detail: { form: confirmedForm } }));
            modal.close();
            confirmedForm.requestSubmit();
            confirmedForm = null;
        });
    }
    const form = root.querySelector('#lu-search');
    if (!form) return;
    const input = form.querySelector('#user_search_box');
    const clear = form.querySelector('[type=reset]');
    clear.hidden = !input.value.length;
    const original = root.querySelector('#lu-users');
    const results = root.querySelector('#lu-results');
    const status = root.querySelector('#lu-search-status');
    const pagination = root.querySelector('#lu-pagination');
    const avatarColumn = @json((bool) config('laravelusers.avatar.enabled', false));
    const bulk = @json((bool) config('laravelusers.bulkActions', false));
    const roles = @json((bool) config('laravelusers.rolesEnabled'));
    const createdColumn = @json((bool) config('laravelusers.showCreatedColumn', true));
    const updatedColumn = @json((bool) config('laravelusers.showUpdatedColumn', true));
    const onlineColumn = @json((bool) (config('laravelusers.activity.online', false) && config('laravelusers.showOnlineColumn', false)));
    const loginColumn = @json((bool) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginColumn', false)));
    const loginDetailsColumn = @json((bool) (config('laravelusers.activity.login', false) && config('laravelusers.showLastLoginDetailsColumn', false)));
    const emailLinks = @json((bool) config('laravelusers.emailLinks', false));
    const currentUser = @json(Auth::id());
    const baseUrl = @json(url('users'));
    const delay = @json(max(0, (int) config('laravelusers.searchDebounce', 2000)));
    let request;
    let timer;
    function reset() {
        clearTimeout(timer);
        if (request) request.abort();
        clear.hidden = true;
        original.hidden = false;
        results.hidden = true;
        results.replaceChildren();
        status.hidden = true;
        if (pagination) pagination.hidden = false;
        root.dispatchEvent(new Event('lu:rows'));
    }
    function cell(row, value) {
        const td = document.createElement('td');
        td.textContent = value == null ? '' : value;
        row.append(td);
        return td;
    }
    function dateCell(row, value, empty = '') {
        const td = cell(row, value || '');
        if (value) td.dataset.luDate = value;
        else if (empty) {
            const label = document.createElement('span');
            label.className = 'lu-date'; label.textContent = empty;
            td.append(label);
        }
    }
    form.addEventListener('reset', reset);
    input.addEventListener('input', function () {
        clearTimeout(timer);
        if (request) request.abort();
        clear.hidden = !input.value.length;
        if (!input.value.length) return reset();
        if (!@json((bool) config('laravelusers.searchDebounceEnabled', false))) return;
        timer = setTimeout(() => form.requestSubmit(), delay);
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearTimeout(timer);
        if (request) request.abort();
        const current = new AbortController();
        request = current;
        status.hidden = false;
        status.textContent = @json(__('laravelusers::ui.searching'));
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }, signal: current.signal
            });
            if (!response.ok) throw new Error('Search failed');
            const payload = await response.json();
            const users = Array.isArray(payload) ? payload : payload.users;
            const activity = payload.activity || {};
            const avatars = payload.avatars || {};
            if (current !== request || current.signal.aborted) return;
            results.replaceChildren();
            users.forEach(function (user) {
                const row = document.createElement('tr');
                row.dataset.luUser = String(user.id);
                if (avatarColumn) {
                    const details = avatars[user.id] || { initials: '?', size: 40, fallback: 'icon' };
                    const fragment = root.querySelector('#lu-avatar-template').content.cloneNode(true);
                    const avatar = fragment.querySelector('.lu-avatar');
                    avatar.style.width = details.size + 'px';
                    avatar.style.height = details.size + 'px';
                    const initials = avatar.querySelector('[data-lu-initials]');
                    initials.textContent = details.initials;
                    initials.hidden = details.fallback !== 'initials';
                    avatar.querySelector('svg').hidden = details.fallback === 'initials';
                    if (details.fallback === 'initials') avatar.querySelector('svg').setAttribute('hidden', '');
                    if (details.src) {
                        const image = document.createElement('img');
                        image.alt = '';
                        image.loading = 'lazy';
                        image.referrerPolicy = 'no-referrer';
                        image.src = details.src;
                        avatar.append(image);
                    }
                    cell(row, '').append(fragment);
                }
                if (bulk) {
                    const selection = cell(row, '');
                    selection.dataset.luSelectionCell = '';
                    if (String(user.id) !== String(currentUser) && @json(\jeremykenedy\laravelusers\Support\UserAccess::selectable())) {
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox'; checkbox.value = user.id; checkbox.dataset.luSelect = '';
                        checkbox.setAttribute('aria-label', @json(__('laravelusers::ui.select_user', ['name' => ':name'])).replace(':name', user.name));
                        selection.append(checkbox);
                    }
                }
                cell(row, user.id);
                const name = cell(row, '');
                const link = document.createElement('a');
                link.href = baseUrl + '/' + encodeURIComponent(user.id);
                link.textContent = user.name;
                if (tooltips) link.title = @json(__('laravelusers::ui.view_user'));
                name.append(link);
                const email = cell(row, emailLinks ? '' : user.email);
                if (emailLinks) {
                    const mail = document.createElement('a');
                    mail.href = 'mailto:' + user.email;
                    mail.textContent = user.email;
                    if (tooltips) mail.title = @json(__('laravelusers::ui.email_user'));
                    email.append(mail);
                }
                if (roles) cell(row, (user.roles || []).map(role => role.name).join(', '));
                const details = activity[user.id] || {};
                if (onlineColumn) {
                    const presence = cell(row, '');
                    presence.dataset.luValue = details.online === true ? 'online' : 'offline';
                    if (details.online === true) {
                        const badge = document.createElement('span');
                        badge.className = 'lu-badge lu-online';
                        badge.textContent = @json(__('laravelusers::ui.online'));
                        presence.append(badge);
                    }
                }
                if (createdColumn) dateCell(row, user.created_at);
                if (updatedColumn) dateCell(row, user.updated_at);
                if (loginColumn) dateCell(row, details.last_login_at, @json(__('laravelusers::ui.no_logins')));
                if (loginDetailsColumn) {
                    const summary = ['device', 'os', 'browser', 'ip_address'].map(field => details[field]).filter(Boolean).join(' / ');
                    const detail = document.createElement('span');
                    detail.className = 'lu-login-details'; detail.title = summary;
                    ['device', 'os', 'browser', 'ip_address'].forEach(field => {
                        if (!details[field]) return;
                        const item = document.createElement('span');
                        item.dataset.luLoginField = field;
                        if (field === 'ip_address') {
                            const link = document.createElement('a');
                            link.href = 'https://ipinfo.io/' + encodeURIComponent(details[field]);
                            link.target = '_blank';
                            link.rel = 'noopener noreferrer';
                            link.title = @json(__('laravelusers::ui.lookup_ip'));
                            link.setAttribute('aria-label', @json(__('laravelusers::ui.lookup_ip')) + ': ' + details[field]);
                            link.textContent = details[field];
                            item.append(link);
                        } else {
                            item.textContent = details[field];
                        }
                        detail.append(item);
                    });
                    cell(row, '').append(detail);
                }
                const actions = root.querySelector('#lu-row-actions').content.cloneNode(true);
                actions.querySelector('[data-lu-show]').href = link.href;
                const edit = actions.querySelector('[data-lu-edit]');
                if (edit) edit.href = link.href + '/edit';
                actions.querySelectorAll('[data-lu-email-action]').forEach(button => { button.dataset.luEmailUser = user.id; button.dataset.luEmailName = user.name; });
                const deletion = actions.querySelector('form');
                if (deletion && String(user.id) === String(currentUser)) deletion.remove();
                else if (deletion) {
                    deletion.action = link.href;
                    if (deletion.hasAttribute('data-lu-confirm')) deletion.dataset.luConfirm = @json(__('laravelusers::ui.confirm_delete', ['name' => ':name'])).replace(':name', user.name);
                }
                cell(row, '').append(actions);
                results.append(row);
            });
            if (!users.length) {
                const row = document.createElement('tr');
                cell(row, @json(__('laravelusers::laravelusers.search.no-results'))).colSpan = 4 + Number(bulk) + Number(avatarColumn) + Number(createdColumn) + Number(updatedColumn) + Number(roles) + Number(onlineColumn) + Number(loginColumn) + Number(loginDetailsColumn);
                results.append(row);
            }
            original.hidden = true;
            results.hidden = false;
            if (pagination) pagination.hidden = true;
            status.textContent = @json(__('laravelusers::ui.results', ['count' => ':count'])).replace(':count', users.length);
            root.dispatchEvent(new Event('lu:rows'));
            root.dispatchEvent(new CustomEvent('lu:appearance', {detail: payload.appearance || {}}));
        } catch (error) {
            if (error.name === 'AbortError' || current !== request) return;
            original.hidden = false;
            results.hidden = true;
            if (pagination) pagination.hidden = false;
            status.textContent = @json(__('laravelusers::ui.search_error'));
        }
    });
})();
</script>
