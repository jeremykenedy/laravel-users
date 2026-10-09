@if(config('laravelusers.columnVisibility', false) || config('laravelusers.responsiveTable', false) || config('laravelusers.tableViewToggle', false) || config('laravelusers.bulkActions', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    const table = root && root.querySelector('[data-lu-table]');
    if (!table) return;
    @include('laravelusers::scripts.table-headers')
    @include('laravelusers::partials.card-labels')
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
                cell.classList.remove('lu-card-last-field');
                cell.dataset.luLabel = label;
                if (row.parentElement.tagName === 'TBODY' && row.cells.length === headers.length && !cell.querySelector('.lu-card-label') && !headers[column].hasAttribute('data-lu-no-sort') && !headers[column].classList.contains('no-sort')) {
                    const title = document.createElement('span');
                    title.className = 'lu-card-label'; title.dataset.luLabel = label;
                    const template = document.querySelector('[data-lu-icon-template="' + (cardIcons[label] || 'id') + '"]');
                    if (template) title.append(template.content.cloneNode(true));
                    cell.prepend(title);
                }
                if (row.parentElement.tagName === 'TBODY') cell.dataset.luColumn = label;
                cell.hidden = !headers[column].hasAttribute('data-lu-required') && saved[label] === false;
            });
            if (row.parentElement.tagName === 'TBODY') {
                const fields = Array.from(row.cells).filter(cell => !cell.hidden && !cell.hasAttribute('data-lu-empty-action') && !cell.hasAttribute('data-lu-selection-cell') && !cell.querySelector('.lu-actions, .btn, .lu-avatar, [data-lu-select]'));
                fields[fields.length - 1]?.classList.add('lu-card-last-field');
            }
        });
    }
    if (@json((bool) config('laravelusers.columnVisibility', false))) {
        const menu = document.createElement('details');
        menu.className = 'lu-columns';
        const summary = document.createElement('summary');
        summary.textContent = @json(__('laravelusers::ui.columns'));
        summary.setAttribute('aria-label', @json(__('laravelusers::ui.columns')));
        if (@json((bool) config('laravelusers.tooltipsEnabled', true))) summary.title = summary.textContent;
        const template = document.querySelector('[data-lu-icon-template="columns"]');
        if (template) summary.prepend(template.content.cloneNode(true));
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
        document.addEventListener('click', function (event) {
            if (!menu.contains(event.target)) menu.open = false;
        });
    }
    const controls = [root.querySelector('.lu-view-toggle'), root.querySelector('.lu-columns'), root.querySelector('.lu-mobile-filters'), root.querySelector('.lu-mobile-sort'), root.querySelector('.lu-selection-controls')].filter(Boolean);
    if (controls.length) {
        const toolbar = document.createElement('div');
        toolbar.className = 'lu-table-toolbar';
        table.parentElement.before(toolbar);
        controls.forEach(function (control) {
            if (control.classList.contains('lu-view-toggle') || control.classList.contains('lu-selection-controls')) {
                toolbar.append(...control.children); control.remove();
            } else toolbar.append(control);
        });
    }
    apply();
    root.addEventListener('lu:rows', apply);
})();
</script>
@endif
