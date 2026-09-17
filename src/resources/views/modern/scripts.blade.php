<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    root.addEventListener('submit', function (event) {
        if (event.target.dataset.luConfirm && !window.confirm(event.target.dataset.luConfirm)) event.preventDefault();
    });
    const form = root.querySelector('#lu-search');
    if (!form) return;
    const original = root.querySelector('#lu-users');
    const results = root.querySelector('#lu-results');
    const status = root.querySelector('#lu-search-status');
    const pagination = root.querySelector('#lu-pagination');
    const roles = @json((bool) config('laravelusers.rolesEnabled'));
    const baseUrl = @json(url('users'));
    let request;
    function reset() {
        if (request) request.abort();
        original.hidden = false;
        results.hidden = true;
        results.replaceChildren();
        status.hidden = true;
        if (pagination) pagination.hidden = false;
    }
    function cell(row, value) {
        const td = document.createElement('td');
        td.textContent = value == null ? '' : value;
        row.append(td);
        return td;
    }
    form.addEventListener('reset', reset);
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
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
            const users = await response.json();
            if (current !== request || current.signal.aborted) return;
            results.replaceChildren();
            users.forEach(function (user) {
                const row = document.createElement('tr');
                cell(row, user.id);
                const name = cell(row, '');
                const link = document.createElement('a');
                link.href = baseUrl + '/' + encodeURIComponent(user.id);
                link.textContent = user.name;
                name.append(link);
                cell(row, user.email);
                if (roles) cell(row, (user.roles || []).map(function (role) { return role.name; }).join(', '));
                const actions = cell(row, '');
                const edit = document.createElement('a');
                edit.href = link.href + '/edit';
                edit.className = 'lu-button lu-secondary';
                edit.textContent = @json(__('laravelusers::ui.edit'));
                actions.append(edit);
                results.append(row);
            });
            if (!users.length) {
                const row = document.createElement('tr');
                cell(row, @json(__('laravelusers::laravelusers.search.no-results'))).colSpan = roles ? 5 : 4;
                results.append(row);
            }
            original.hidden = true;
            results.hidden = false;
            if (pagination) pagination.hidden = true;
            status.textContent = @json(__('laravelusers::ui.results', ['count' => ':count'])).replace(':count', users.length);
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
