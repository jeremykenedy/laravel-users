@if(config('laravelusers.bulkActions', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    const form = root && root.querySelector('#lu-bulk');
    if (!form) return;
    const all = Array.from(root.querySelectorAll('[data-lu-select-all], [data-lu-select-all-mobile]'));
    function available() {
        return Array.from(root.querySelectorAll('[data-lu-select]:not(:disabled)')).filter(input => !input.closest('tr').hidden && !input.closest('tbody').hidden && input.closest('tbody').style.display !== 'none');
    }
    function update() {
        const inputs = available();
        const selected = inputs.filter(input => input.checked);
        const values = form.querySelector('[data-lu-selected-inputs]');
        values.replaceChildren();
        selected.forEach(function (checkbox) {
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'ids[]'; input.value = checkbox.value;
            values.append(input);
        });
        form.querySelector('[data-lu-selected-count]').textContent = @json(__('laravelusers::ui.selected', ['count' => ':count'])).replace(':count', selected.length);
        form.querySelector('#lu-bulk-submit').disabled = !selected.length;
        all.forEach(input => { input.checked = inputs.length > 0 && selected.length === inputs.length; input.indeterminate = selected.length > 0 && selected.length < inputs.length; });
    }
    all.forEach(checkbox => checkbox.addEventListener('change', function () { available().forEach(input => { input.checked = checkbox.checked; }); update(); }));
    root.addEventListener('change', update);
    root.addEventListener('input', function (event) { if (event.target.matches('.lu-column-filter')) update(); });
    root.addEventListener('lu:rows', function () { root.querySelectorAll('[data-lu-select]').forEach(input => { input.checked = false; }); update(); });
    update();
})();
</script>
@endif
