(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    const options = JSON.parse(document.getElementById('lu-page-options').textContent);
    const tooltips = options.tooltips;
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
        modal.querySelector('#lu-confirm-title').textContent = form.dataset.luConfirmTitle || options.deleteTitle;
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
    const avatarColumn = options.avatarColumn;
    const bulk = options.bulk;
    const roles = options.roles;
    const createdColumn = options.createdColumn;
    const updatedColumn = options.updatedColumn;
    const onlineColumn = options.onlineColumn;
    const loginColumn = options.loginColumn;
    const loginDetailsColumn = options.loginDetailsColumn;
    const emailLinks = options.emailLinks;
    const currentUser = options.currentUser;
    const baseUrl = options.usersUrl;
    const delay = options.searchDelay;
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
    function avatarCell(row, details) {
        if (!avatarColumn) return;
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
    function selectionCell(row, user) {
        if (!bulk) return;
        const selection = cell(row, '');
        selection.dataset.luSelectionCell = '';
        if (String(user.id) === String(currentUser) || !options.selectable) return;
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox'; checkbox.value = user.id; checkbox.dataset.luSelect = '';
        checkbox.setAttribute('aria-label', options.selectUser.replace(':name', user.name));
        selection.append(checkbox);
    }
    function emailCell(row, email) {
        const td = cell(row, emailLinks ? '' : email);
        if (!emailLinks) return;
        const link = document.createElement('a');
        link.href = 'mailto:' + email;
        link.textContent = email;
        if (tooltips) link.title = options.emailUser;
        td.append(link);
    }
    function presenceCell(row, details) {
        if (!onlineColumn) return;
        const presence = cell(row, '');
        presence.dataset.luValue = details.online === true ? 'online' : 'offline';
        if (details.online !== true) return;
        const badge = document.createElement('span');
        badge.className = 'lu-badge lu-online';
        badge.textContent = options.online;
        presence.append(badge);
    }
    function loginDetailsCell(row, details) {
        if (!loginDetailsColumn) return;
        const fields = [
            ['device', details.device],
            ['os', details.os],
            ['browser', details.browser],
            ['ip_address', details.ip_address]
        ];
        const detail = document.createElement('span');
        detail.className = 'lu-login-details';
        detail.title = fields.map(([, value]) => value).filter(Boolean).join(' / ');
        fields.forEach(([field, value]) => {
            if (!value) return;
            const item = document.createElement('span');
            item.dataset.luLoginField = field;
            if (field === 'ip_address') {
                const link = document.createElement('a');
                link.href = 'https://ipinfo.io/' + encodeURIComponent(value);
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
                link.title = options.lookupIp;
                link.setAttribute('aria-label', options.lookupIp + ': ' + value);
                link.textContent = value;
                item.append(link);
            } else {
                item.textContent = value;
            }
            detail.append(item);
        });
        cell(row, '').append(detail);
    }
    function actionCell(row, user, userUrl) {
        const actions = root.querySelector('#lu-row-actions').content.cloneNode(true);
        actions.querySelector('[data-lu-show]').href = userUrl;
        const edit = actions.querySelector('[data-lu-edit]');
        if (edit) edit.href = userUrl + '/edit';
        actions.querySelectorAll('[data-lu-user-action]').forEach(function (action) {
            action.action = action.dataset.luActionTemplate.replace('__USER_ID__', encodeURIComponent(user.id));
            if (String(user.id) === String(currentUser)) action.remove();
        });
        actions.querySelectorAll('[data-lu-email-action]').forEach(button => { button.dataset.luEmailUser = user.id; button.dataset.luEmailName = user.name; });
        const deletion = actions.querySelector('[data-lu-delete-action]');
        if (deletion && String(user.id) === String(currentUser)) deletion.remove();
        else if (deletion) {
            deletion.action = userUrl;
            if (deletion.hasAttribute('data-lu-confirm')) deletion.dataset.luConfirm = options.confirmDelete.replace(':name', user.name);
        }
        cell(row, '').append(actions);
    }
    function renderUser(user, activity, avatars) {
        const row = document.createElement('tr');
        row.dataset.luUser = String(user.id);
        avatarCell(row, avatars[user.id] || { initials: '?', size: 40, fallback: 'icon' });
        selectionCell(row, user);
        cell(row, user.id);
        const name = cell(row, '');
        const link = document.createElement('a');
        link.href = baseUrl + '/' + encodeURIComponent(user.id);
        link.textContent = user.name;
        if (tooltips) link.title = options.viewUser;
        name.append(link);
        emailCell(row, user.email);
        if (roles) cell(row, (user.roles || []).map(role => role.name).join(', '));
        const details = activity[user.id] || {};
        presenceCell(row, details);
        if (createdColumn) dateCell(row, user.created_at);
        if (updatedColumn) dateCell(row, user.updated_at);
        if (loginColumn) dateCell(row, details.last_login_at, options.noLogins);
        loginDetailsCell(row, details);
        actionCell(row, user, link.href);
        results.append(row);
    }
    form.addEventListener('reset', reset);
    input.addEventListener('input', function () {
        clearTimeout(timer);
        if (request) request.abort();
        clear.hidden = !input.value.length;
        if (!input.value.length) {
            reset();
            return;
        }
        if (!options.searchDebounce) return;
        timer = setTimeout(() => form.requestSubmit(), delay);
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearTimeout(timer);
        if (request) request.abort();
        const current = new AbortController();
        request = current;
        status.hidden = false;
        status.textContent = options.searching;
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
            users.forEach(user => renderUser(user, activity, avatars));
            if (!users.length) {
                const row = document.createElement('tr');
                cell(row, options.noResults).colSpan = 4 + Number(bulk) + Number(avatarColumn) + Number(createdColumn) + Number(updatedColumn) + Number(roles) + Number(onlineColumn) + Number(loginColumn) + Number(loginDetailsColumn);
                results.append(row);
            }
            original.hidden = true;
            results.hidden = false;
            if (pagination) pagination.hidden = true;
            status.textContent = options.results.replace(':count', users.length);
            root.dispatchEvent(new Event('lu:rows'));
            root.dispatchEvent(new CustomEvent('lu:appearance', {detail: payload.appearance || {}}));
        } catch (error) {
            if (error.name === 'AbortError' || current !== request) return;
            original.hidden = false;
            results.hidden = true;
            if (pagination) pagination.hidden = false;
            status.textContent = options.searchError;
        }
    });
})();
