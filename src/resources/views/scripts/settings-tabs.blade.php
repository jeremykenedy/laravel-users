<script>
(function () {
    const root = document.getElementById('laravelusers');
    const tabs = Array.from(root?.querySelectorAll('[data-lu-settings-tab]') || []);
    if (!tabs.length) return;
    function show(tab, focus = false) {
        tabs.forEach(button => { const active = button === tab; button.setAttribute('aria-selected', String(active)); button.tabIndex = active ? 0 : -1; });
        root.querySelectorAll('[data-lu-settings-panel]').forEach(panel => { panel.hidden = panel.dataset.luSettingsPanel !== tab.dataset.luSettingsTab; });
        root.querySelectorAll('[data-lu-settings-form]').forEach(form => { form.hidden = !Array.from(form.querySelectorAll('[data-lu-settings-panel]')).some(panel => !panel.hidden); });
        if (focus) tab.focus();
    }
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => { show(tab); history.replaceState(null, '', `#${tab.dataset.luSettingsTab}`); });
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
            if (next !== null) { event.preventDefault(); show(tabs[next], true); }
        });
    });
    const invalid = root.querySelector('[aria-invalid="true"]')?.closest('[data-lu-settings-panel]');
    show(tabs.find(tab => tab.dataset.luSettingsTab === (invalid?.dataset.luSettingsPanel || location.hash.slice(1))) || tabs[0]);
})();
</script>
