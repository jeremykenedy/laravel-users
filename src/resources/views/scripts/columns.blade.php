@if(config('laravelusers.columnVisibility', false) || config('laravelusers.responsiveTable', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    const table = root && root.querySelector('[data-lu-table]');
    if (!table) return;
    @include('laravelusers::scripts.table-headers')
    const labels = headers.map(header => header.dataset.luLabel || header.textContent.trim());
    const key = 'laravelusers.columns.' + (table.dataset.luView || 'users');
    let saved = {};
    if (@json((bool) config('laravelusers.columnVisibility', false))) {
        try { saved = JSON.parse(localStorage.getItem(key)) || {}; } catch (error) {}
    }
    function apply() {
        const merged = table.querySelector('[data-lu-avatar-label]');
        headers.forEach(header => { header.hidden = !header.hasAttribute('data-lu-required') && saved[header.dataset.luLabel || header.textContent.trim()] === false; });
        if (merged) merged.colSpan = saved[merged.dataset.luAvatarLabel] === false ? 1 : 2;
        Array.from(table.rows).forEach(function (row) {
            if (row.cells.length !== headers.length) return;
            Array.from(row.cells).forEach(function (cell, column) {
                const label = labels[column] || @json(__('laravelusers::laravelusers.users-table.actions'));
                cell.dataset.luLabel = label;
                if (row.parentElement.tagName === 'TBODY') cell.dataset.luColumn = label;
                cell.hidden = !headers[column].hasAttribute('data-lu-required') && saved[label] === false;
            });
        });
    }
    if (@json((bool) config('laravelusers.columnVisibility', false))) {
        const menu = document.createElement('details');
        menu.className = 'lu-columns';
        const summary = document.createElement('summary');
        summary.textContent = @json(__('laravelusers::ui.columns'));
        menu.append(summary);
        const choices = document.createElement('div');
        choices.className = 'lu-column-options';
        menu.append(choices);
        labels.forEach(function (label, column) {
            if (!label || headers[column].hasAttribute('data-lu-required')) return;
            const choice = document.createElement('label');
            const input = document.createElement('input');
            input.type = 'checkbox'; input.checked = saved[label] !== false;
            choice.append(input, document.createTextNode(' ' + label));
            choices.append(choice);
            input.addEventListener('change', function () {
                saved[label] = input.checked;
                try { localStorage.setItem(key, JSON.stringify(saved)); } catch (error) {}
                apply();
            });
        });
        table.parentElement.before(menu);
    }
    apply();
    root.addEventListener('lu:rows', apply);
})();
</script>
@endif
