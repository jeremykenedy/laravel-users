<script>
(function () {
    const form = document.querySelector('#laravelusers .lu-edit-card .lu-form');
    const tabs = Array.from(form?.querySelectorAll('[data-lu-edit-tab]') || []);
    if (!tabs.length) return;
    function show(tab, focus = false) {
        tabs.forEach(button => {
            button.setAttribute('aria-selected', String(button === tab));
            button.tabIndex = button === tab ? 0 : -1;
        });
        form.querySelectorAll('[data-lu-edit-panel]').forEach(panel => {
            panel.hidden = panel.dataset.luEditPanel !== tab.dataset.luEditTab;
        });
        if (focus) tab.focus();
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => show(tab));
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
            if (next !== null) { event.preventDefault(); show(tabs[next], true); }
        });
    });
    form.addEventListener('invalid', event => {
        const panel = event.target.closest('[data-lu-edit-panel]');
        const tab = tabs.find(button => button.dataset.luEditTab === panel?.dataset.luEditPanel);
        if (tab) show(tab);
    }, true);
    const error = form.querySelector('.lu-field-error')?.closest('[data-lu-edit-panel]');
    show(tabs.find(tab => tab.dataset.luEditTab === error?.dataset.luEditPanel) || tabs[0]);
})();
</script>
