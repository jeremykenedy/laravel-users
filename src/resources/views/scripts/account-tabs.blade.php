<script>
(function () {
    const root = document.querySelector('#laravelusers [data-lu-account-tabs]');
    if (!root) return;
    const tabs = Array.from(root.querySelectorAll('[data-lu-account-tab]'));
    const panels = Array.from(document.querySelectorAll('#laravelusers [data-lu-account-panel]'));
    if (!tabs.length || !panels.length) return;
    const storageKey = 'laravelusers-account-tab';
    let stored = null;
    try {
        stored = sessionStorage.getItem(storageKey);
        sessionStorage.removeItem(storageKey);
    } catch (error) {}
    const initial = tabs.find(tab => tab.dataset.luAccountTab === stored)
        || tabs.find(tab => tab.getAttribute('aria-selected') === 'true') || tabs[0];
    function show(tab, focus = false) {
        tabs.forEach(button => {
            const selected = button === tab;
            button.setAttribute('aria-selected', String(selected));
            button.tabIndex = selected ? 0 : -1;
        });
        panels.forEach(panel => {
            panel.hidden = panel.dataset.luAccountPanel !== tab.dataset.luAccountTab;
        });
        if (focus) tab.focus();
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => show(tab));
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length
                : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length
                    : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
            if (next !== null) {
                event.preventDefault();
                show(tabs[next], true);
            }
        });
    });
    root.closest('.lu-account-content')?.querySelectorAll('form').forEach(form => form.addEventListener('submit', () => {
        try {
            sessionStorage.setItem(storageKey, form.dataset.luAccountReturnTab || form.querySelector('[name="account_tab"]')?.value || form.querySelector('[name="section"]')?.value || initial.dataset.luAccountTab);
        } catch (error) {}
    }));
    show(initial);
})();
</script>
