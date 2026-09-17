<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    const select = root.querySelector('#lu-theme');
    const media = window.matchMedia('(prefers-color-scheme: dark)');
    let preference = root.dataset.luTheme;
    if (select) {
        try { preference = localStorage.getItem('laravelusers.theme') || preference; } catch (error) {}
    }
    if (!['light', 'dark', 'system'].includes(preference)) preference = 'light';
    function apply() {
        const theme = preference === 'system' ? (media.matches ? 'dark' : 'light') : preference;
        root.dataset.luTheme = theme;
        root.dataset.bsTheme = theme;
        if (select) select.value = preference;
    }
    if (select) select.addEventListener('change', function () {
        preference = select.value;
        try { localStorage.setItem('laravelusers.theme', preference); } catch (error) {}
        apply();
    });
    if (media.addEventListener) media.addEventListener('change', apply);
    apply();
})();
</script>
