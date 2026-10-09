<script>
(function () {
    const root = document.getElementById('laravelusers');
    const table = root?.querySelector('[data-lu-table]');
    if (!table || !@json((bool) config('laravelusers.tableTextMaxWidth', 240))) return;
    @include('laravelusers::scripts.table-headers')
    const labels = @json([__('laravelusers::laravelusers.users-table.name'), __('laravelusers::laravelusers.users-table.email')]);
    const observer = new ResizeObserver(entries => {
        entries.forEach(({target}) => {
            if (target.scrollWidth > target.clientWidth && root.dataset.luTableView !== 'cards') target.tabIndex = 0;
            else target.removeAttribute('tabindex');
        });
    });
    function apply() {
        headers.forEach((header, index) => {
            if (!labels.includes(header.dataset.luLabel || header.textContent.trim())) return;
            Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
                const cell = row.cells[index];
                if (!cell || row.cells.length !== headers.length || cell.querySelector('.lu-table-text')) return;
                const content = document.createElement('span');
                content.className = 'lu-table-text';
                Array.from(cell.childNodes).filter(node => !node.classList?.contains('lu-card-label')).forEach(node => content.append(node));
                cell.append(content);
                observer.observe(content);
            }));
        });
    }
    root.addEventListener('lu:rows', apply);
    apply();
})();
</script>
