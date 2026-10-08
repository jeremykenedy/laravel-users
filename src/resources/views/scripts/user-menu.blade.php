<script>
(function () {
    document.querySelectorAll(':where(#laravelusers, .lu-user-menu-component) .lu-user-menu').forEach(menu => {
    if (menu.dataset.luReady) return;
    menu.dataset.luReady = 'true';
    document.addEventListener('click', function (event) { if (!menu.contains(event.target)) menu.open = false; });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && menu.open) {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });
    });
})();
</script>
