@if(config('laravelusers.tableSorting', false) || config('laravelusers.tableFiltering', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    const table = root && root.querySelector('[data-lu-table]');
    if (!table) return;
    const sorting = @json((bool) config('laravelusers.tableSorting', false));
    const filtering = @json((bool) config('laravelusers.tableFiltering', false));
    @include('laravelusers::scripts.table-headers')
    const filters = [];
    const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
    let sortColumn;
    let descending = false;
    function value(row, column) {
        return row.cells[column].dataset.luValue || row.cells[column].textContent.trim();
    }
    function apply() {
        Array.from(table.tBodies).filter(body => !body.hidden && body.style.display !== 'none').forEach(function (body) {
            const rows = Array.from(body.rows).filter(row => row.cells.length === headers.length);
            rows.forEach(row => { row.hidden = filters.some((filter, column) => filter && !value(row, column).toLocaleLowerCase().includes(filter.value.toLocaleLowerCase())); });
            if (sortColumn !== undefined) {
                rows.sort((a, b) => collator.compare(value(a, sortColumn), value(b, sortColumn)) * (descending ? -1 : 1));
                rows.forEach(row => body.append(row));
            }
        });
    }
    const filterRow = filtering ? table.tHead.insertRow() : null;
    headers.forEach(function (header, column) {
        const label = header.dataset.luLabel || header.textContent.trim();
        header.dataset.luLabel = label;
        const excluded = header.hasAttribute('data-lu-no-sort') || header.classList.contains('no-sort');
        if (sorting && !excluded) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'lu-sort';
            button.textContent = label + ' ↕';
            button.setAttribute('aria-label', @json(__('laravelusers::ui.sort_column', ['column' => ':column'])).replace(':column', label));
            header.replaceChildren(button);
            header.setAttribute('aria-sort', 'none');
            button.addEventListener('click', function () {
                descending = sortColumn === column ? !descending : false;
                sortColumn = column;
                headers.forEach(th => { if (th.hasAttribute('aria-sort')) th.setAttribute('aria-sort', 'none'); });
                header.setAttribute('aria-sort', descending ? 'descending' : 'ascending');
                apply();
            });
        }
        if (filtering) {
            const cell = document.createElement('td');
            filterRow.append(cell);
            if (excluded) return;
            const input = document.createElement('input');
            input.type = 'search';
            input.placeholder = label;
            input.className = 'lu-column-filter';
            input.setAttribute('aria-label', @json(__('laravelusers::ui.filter_column', ['column' => ':column'])).replace(':column', label));
            filters[column] = input;
            cell.append(input);
            input.addEventListener('input', apply);
        }
    });
    if (sorting && @json((bool) config('laravelusers.responsiveTable', false))) {
        const toolbar = document.createElement('div');
        toolbar.className = 'lu-mobile-sort';
        const label = document.createElement('label');
        label.textContent = @json(__('laravelusers::ui.sort')); label.htmlFor = 'lu-mobile-sort';
        const select = document.createElement('select');
        select.id = 'lu-mobile-sort'; select.className = 'lu-column-filter';
        headers.forEach(function (header, column) {
            if (!header.hasAttribute('aria-sort')) return;
            ['ascending', 'descending'].forEach(function (direction) {
                const option = document.createElement('option');
                option.value = column + ':' + direction;
                option.textContent = header.dataset.luLabel + ' (' + (direction === 'ascending' ? @json(__('laravelusers::ui.ascending')) : @json(__('laravelusers::ui.descending'))) + ')';
                select.append(option);
            });
        });
        select.addEventListener('change', function () {
            const parts = select.value.split(':');
            sortColumn = Number(parts[0]); descending = parts[1] === 'descending';
            headers.forEach(header => { if (header.hasAttribute('aria-sort')) header.setAttribute('aria-sort', 'none'); });
            headers[sortColumn].setAttribute('aria-sort', parts[1]);
            apply();
        });
        toolbar.append(label, select); table.parentElement.before(toolbar);
    }
    if (filtering && @json((bool) config('laravelusers.responsiveTable', false))) {
        const menu = document.createElement('details'); menu.className = 'lu-mobile-filters';
        const summary = document.createElement('summary'); summary.textContent = @json(__('laravelusers::ui.filters'));
        const fields = document.createElement('div'); fields.className = 'lu-mobile-filter-fields';
        filters.forEach(function (input, column) {
            const mobile = input.cloneNode();
            mobile.setAttribute('aria-label', @json(__('laravelusers::ui.mobile_filter', ['column' => ':column'])).replace(':column', headers[column].dataset.luLabel));
            mobile.addEventListener('input', function () { input.value = mobile.value; apply(); });
            input.addEventListener('input', function () { mobile.value = input.value; });
            fields.append(mobile);
        });
        menu.append(summary, fields); table.parentElement.before(menu);
    }
    root.addEventListener('lu:rows', apply);
})();
</script>
@endif
