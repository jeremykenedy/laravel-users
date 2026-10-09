<script>
(function () {
    function initialize() {
    const buttons = Array.from(document.querySelectorAll('[data-lu-theme-target], #lu-theme'));
    const targets = new Map();
    const packageRoot = document.getElementById('laravelusers');
    if (packageRoot) targets.set(packageRoot, []);
    buttons.forEach(toggle => {
        let root;
        try { root = document.querySelector(toggle.dataset.luThemeTarget || '#laravelusers'); } catch (error) { return; }
        if (!root) return;
        if (!targets.has(root)) targets.set(root, []);
        targets.get(root).push(toggle);
    });
    targets.forEach((toggles, root) => {
    if (root.dataset.luThemeReady) return;
    root.dataset.luThemeReady = 'true';
    const modes = ['light', 'dark', 'system'];
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = root.dataset.luTheme || @json(\jeremykenedy\laravelusers\Support\Frontend::theme());
    if (toggles.length) {
        try { preference = localStorage.getItem('laravelusers.theme') || preference; } catch (error) {}
    }
    if (!modes.includes(preference)) preference = 'light';
    function apply() {
        const theme = preference === 'system' ? (media.matches ? 'dark' : 'light') : preference;
        root.dataset.luTheme = theme;
        root.dataset.bsTheme = theme;
        root.classList.toggle('dark', theme === 'dark');
        root.dispatchEvent(new CustomEvent('lu:theme', {detail: {theme, preference}}));
        toggles.forEach(toggle => {
        if (toggle.tagName === 'SELECT') {
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
        });
    }
    toggles.forEach(toggle => toggle.addEventListener(toggle.tagName === 'SELECT' ? 'change' : 'click', function () {
        preference = toggle.tagName === 'SELECT' ? toggle.value : modes[(modes.indexOf(preference) + 1) % modes.length];
        try { localStorage.setItem('laravelusers.theme', preference); } catch (error) {}
        apply();
    }));
    if (media.addEventListener) media.addEventListener('change', apply);
    else media.addListener(apply);
    apply();
    });
    }
    initialize();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once: true});
})();
</script>
