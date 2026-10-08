<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    const toggle = root.querySelector('#lu-theme');
    const legacySelect = toggle && toggle.tagName === 'SELECT';
    const modes = ['light', 'dark', 'system'];
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = root.dataset.luTheme;
    if (toggle) {
        try { preference = localStorage.getItem('laravelusers.theme') || preference; } catch (error) {}
    }
    if (!modes.includes(preference)) preference = 'light';
    function apply() {
        const theme = preference === 'system' ? (media.matches ? 'dark' : 'light') : preference;
        root.dataset.luTheme = theme;
        root.dataset.bsTheme = theme;
        if (!toggle) return;
        if (legacySelect) {
            toggle.value = preference;
            return;
        }
        toggle.querySelectorAll('[data-theme-icon]').forEach(function (icon) {
            if (icon.dataset.themeIcon === preference) {
                icon.removeAttribute('hidden');
                toggle.setAttribute('aria-label', toggle.dataset.themeLabel + ': ' + icon.dataset.label);
                toggle.setAttribute('title', icon.dataset.label);
            } else {
                icon.setAttribute('hidden', '');
            }
        });
    }
    if (toggle) toggle.addEventListener(legacySelect ? 'change' : 'click', function () {
        preference = legacySelect ? toggle.value : modes[(modes.indexOf(preference) + 1) % modes.length];
        try { localStorage.setItem('laravelusers.theme', preference); } catch (error) {}
        apply();
    });
    if (media.addEventListener) media.addEventListener('change', apply);
    else media.addListener(apply);
    apply();
})();
</script>
